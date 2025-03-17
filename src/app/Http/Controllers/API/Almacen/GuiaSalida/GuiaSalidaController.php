<?php

namespace App\Http\Controllers\API\Almacen\GuiaSalida;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaSalida\DetalleGuiaSalida;
use App\Models\Almacen\GuiaSalida\GuiaSalida;
use App\Models\Almacen\Lote\Lote;
use App\Models\Almacen\Lote\MovimientoLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuiaSalidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function lotesDisponibles($sede, $producto){
        try{
            $lotes = Lote::select('Codigo', 'Serie', 'Cantidad', 'Stock', 'FechaCaducidad')
                ->where('CodigoProducto', $producto)
                ->where('CodigoSede', $sede)
                ->where('Stock', '>', 0)
                ->get();
            return response()->json($lotes, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar los lotes disponibles' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function listarGuiaSalida(Request $request){
        $filtros = $request->all();
        try{
            
            $guiaIngreso = GuiaSalida::all()->where('CodigoSede', $filtros['CodigoSede']);

            return response()->json($guiaIngreso, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar las Guias de Salida' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function listarVentasActivas($sede){
        // $filtros = $request->all();
        try{
            $compras = DB::table('documentoventa')
            ->where('Vigente', 1)
            ->where('CodigoSede', $sede)
            ->select('Codigo', DB::raw("CONCAT(Serie, '-', Numero) as Descripcion"))
            ->get();
            return response()->json($compras, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar las Compras' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function listarDetalleVenta($venta){
        try{

            $sql = "
                SELECT PrVe.Codigo, PrVe.Cantidad - coalesce(Ent.Cantidad, 0) as Cantidad, PrVe.Nombre as Descripcion
                FROM (
                    -- Productos individuales (Tipo = 'B')
                    SELECT P.Codigo, DDV.Cantidad, P.Nombre
                    FROM DetalleDocumentoVenta DDV
                    JOIN Producto P ON P.Codigo = DDV.CodigoProducto
                    WHERE DDV.CodigoVenta = ? AND P.Tipo = 'B'

                    UNION

                    -- Productos dentro de combos (Tipo = 'C' desglosado)
                    SELECT P.Codigo, DDV.Cantidad * PC.Cantidad AS Cantidad, P.Nombre
                    FROM DetalleDocumentoVenta DDV
                    JOIN Producto Co ON Co.Codigo = DDV.CodigoProducto
                    JOIN ProductoCombo PC ON PC.CodigoCombo = Co.Codigo
                    JOIN Producto P ON P.Codigo = PC.CodigoProducto
                    WHERE DDV.CodigoVenta = ? AND Co.Tipo = 'C' AND P.Tipo = 'B'
                ) AS PrVe
                LEFT JOIN (
                    -- Productos ya entregados en guías de salida
                    SELECT dgs.CodigoProducto, SUM(dgs.Cantidad) AS Cantidad
                    FROM guiasalida gs
                    INNER JOIN detalleguiasalida dgs ON dgs.CodigoGuiaSalida = gs.Codigo
                    WHERE gs.CodigoVenta = ?
                    GROUP BY dgs.CodigoProducto
                ) AS Ent ON Ent.CodigoProducto = PrVe.Codigo
                WHERE PrVe.Cantidad > COALESCE(Ent.Cantidad, 0)
            ";

            $resultados = DB::select($sql, [$venta, $venta, $venta]);
                

            return response()->json($resultados, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar el detalle de la venta' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarGuiaSalida(Request $request){
        $guiaData = $request->input('guiaSalida');
        $detalleGuia = $request->input('detalleGuiaSalida');
        DB::beginTransaction();
        try{

            $guiaSalida = GuiaSalida::create($guiaData);

            foreach($detalleGuia as $detalle){
                //Crear el Detalle Guia Salida
                $detalle['CodigoGuiaSalida'] = $guiaSalida->Codigo;
                $CodigoDetalle = DetalleGuiaSalida::create($detalle);

                foreach($detalle['lote'] as $lote){

                    //Consultar el stock de la sede
                    $producto = DB::table('SedeProducto')
                        ->where('CodigoProducto', $detalle['CodigoProducto'])
                        ->where('CodigoSede', $guiaData['CodigoSede'])
                        ->first();
                    
                    //Para calcular el nuevo stock del lote
                    $stockSede = $producto->Stock ?? 0;
                    $costoSede = $producto->CostoCompraPromedio ?? 0;
                    $nuevoStock = $stockSede - $detalle['Cantidad'];

                    //Actualizar el stock del lote
                    DB::table('lote')->where('Codigo', $lote['Codigo'])->decrement('Stock', $lote['Cantidad']);

                    //PARA GENERAR MOVIMIENTO LOTE
                    $movimientoLote['CodigoDetalleSalida'] = $CodigoDetalle->Codigo;
                    $movimientoLote['CodigoLote'] = $lote['Codigo'];
                    $movimientoLote['TipoOperacion'] = 'S';
                    $movimientoLote['Fecha'] = $guiaData['Fecha'];
                    $movimientoLote['Cantidad'] = $nuevoStock;
                    $movimientoLote['CostoPromedio'] = $costoSede;

                    MovimientoLote::create($movimientoLote);
                }

                //Actualizar el stock de la sede
                DB::table('SedeProducto')
                    ->where('CodigoProducto', $detalle['CodigoProducto'])
                    ->where('CodigoSede', $guiaData['CodigoSede'])
                    ->decrement('Stock', $detalle['Cantidad']);

            }
            
            DB::commit();
            return response()->json(['mensaje' => 'Guia de salida registrada'], 201);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registrar la guia de salida' ,'bd' => $e->getMessage()], 500);
        }
    }
}
