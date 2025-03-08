<?php

namespace App\Http\Controllers\API\Almacen\GuiaSalida;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaSalida\DetalleGuiaSalida;
use App\Models\Almacen\GuiaSalida\GuiaSalida;
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
            $resultados = DB::table('detalledocumentoventa as ddv')
            ->leftJoinSub(
                DB::table('guiasalida as gs')
                    ->join('detalleguiasalida as dgs', 'dgs.CodigoGuiaSalida', '=', 'gs.Codigo')
                    ->where('gs.CodigoVenta', $venta)
                    ->groupBy('dgs.CodigoProducto')
                    ->selectRaw('dgs.CodigoProducto, SUM(dgs.Cantidad) as Cantidad'),
                'Entregado',
                'Entregado.CodigoProducto',
                '=',
                'ddv.CodigoProducto'
            )
            ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
            ->where('ddv.CodigoVenta', $venta)
            ->whereRaw('(ddv.Cantidad - COALESCE(Entregado.Cantidad, 0)) > 0')
            ->where('p.Tipo', 'B')
            ->selectRaw('
                ddv.Cantidad - COALESCE(Entregado.Cantidad, 0) as Cantidad,
                ddv.CodigoProducto,
                ddv.Descripcion,
                ((ddv.MontoTotal - ddv.MontoIGV) / (ddv.Cantidad - COALESCE(Entregado.Cantidad, 0))) AS Costo
            ')
            ->get();

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
                $detalle['CodigoGuiaSalida'] = $guiaSalida->Codigo;
                DetalleGuiaSalida::create($detalle);
            }
            
            DB::commit();
            return response()->json(['mensaje' => 'Guia de salida registrada'], 201);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registrar la guia de salida' ,'bd' => $e->getMessage()], 500);
        }
    }
}
