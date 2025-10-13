<?php

namespace App\Http\Controllers\API\Almacen\GuiaSalida;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaSalida\DetalleGuiaSalida;
use App\Models\Almacen\GuiaSalida\GuiaSalida;
use App\Models\Almacen\Lote\Lote;
use App\Models\Almacen\Lote\MovimientoLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function listarPacientes($termino){
        try{
            $resultados = DB::table('personas')
                ->select([
                    'Codigo',
                    DB::raw("CONCAT(Nombres, ' ', Apellidos) as Nombres"),
                    'NumeroDocumento'
                ])
                ->where(function($q) use ($termino) {
                    $q->where('Nombres', 'like', "{$termino}%")
                    ->orWhere('Apellidos', 'like', "{$termino}%")
                    ->orWhere('NumeroDocumento', 'like', "{$termino}%");
                })
                ->get();

            return response()->json($resultados, 200);
            
        }catch(\Exception $e){
            Log::error('Error inesperado al listar pacientes', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarPacientes',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
        }
    }

    public function listarProductosDisponibles(Request $request){

        try{

            $resultados = DB::table('sedeproducto as sp')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->select([
                    'p.Codigo',
                    'p.Nombre',
                    'sp.Stock'
                ])
                ->where('sp.CodigoSede', $request->Sede)
                ->where('sp.Vigente', 1)
                ->where('p.Vigente', 1)
                ->where('sp.Stock', '>', 0)
                ->where('p.Nombre', 'like', "%{$request->termino}%")
                ->orderBy('p.Nombre', 'asc')
                ->get();

            return response()->json($resultados, 200);

        }catch(\Exception $e){
            Log::error('Error inesperado al listar productos disponibles', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarProductosDisponibles',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
        }
        
    }

    public function lotesDisponibles($sede, $producto)
    {
        try {
            $lotes = Lote::select('Codigo', 'Serie', 'Cantidad', 'Stock', 'FechaCaducidad', 'Costo')
                ->where('CodigoProducto', $producto)
                ->where('CodigoSede', $sede)
                ->where('Stock', '>', 0)
                ->where('Vigente', 1)
                ->get();

            // Log de éxito
            Log::info('Lotes listados correctamente', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'lotesDisponibles',
                'cantidad' => count($lotes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($lotes, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al listar Lotes', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'lotesDisponibles',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar los lotes disponibles', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarGuiaSalida(Request $request)
    {
        $filtros   = $request->all();
        $fecha     = $filtros['Fecha'] ?? null;
        $fechaFin    = $filtros['FechaFin'] ?? null;
        $busqueda  = $filtros['Busqueda'] ?? null; // si luego agregas búsqueda opcional
        $sede = $filtros['CodigoSede'] ?? null;

        try {

            $guiaSalida = DB::table('guiasalida as g')
                ->leftJoin('personas as p', 'g.CodigoPaciente', '=', 'p.Codigo')
                ->select(
                    'g.Codigo',
                    'g.CodigoVenta',
                    DB::raw("
                        CASE 
                            WHEN g.TipoDocumento = 'GR' THEN 'Guia de remisión'
                            WHEN g.TipoDocumento = 'F'  THEN 'Factura'
                            WHEN g.TipoDocumento = 'B'  THEN 'Boleta'
                            WHEN g.TipoDocumento = 'D'  THEN 'Guia de remisión - Donante'
                            ELSE 'Desconocido'
                        END AS TipoDocumento
                    "),
                    DB::raw("CONCAT(g.Serie, ' - ', g.Numero) AS Documento"),
                    'g.Fecha',
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) AS Paciente"),
                    'g.Vigente'
                )
                ->where('g.CodigoSede', $sede)
                ->where('g.TipoDocumento', '!=', 'T')
                // Filtro por fecha o rango
                ->when($fecha, function ($query) use ($fecha, $fechaFin) {
                    if (empty($fechaFin)) {
                        $query->whereDate('g.Fecha', $fecha);
                    } else {
                        $query->whereBetween('g.Fecha', [$fecha, $fechaFin]);
                    }
                })
                ->when($busqueda, function ($query, $busqueda) {
                    return $query->where(function ($q) use ($busqueda) {
                        $q->where('p.Nombres', 'like', "%{$busqueda}%")
                        ->orWhere('p.Apellidos', 'like', "%{$busqueda}%");
                    });
                })
                ->get();

            // Log de éxito
            Log::info('Guias de salida listadas correctamente', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarGuiaSalida',
                'cantidad' => count($guiaSalida),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($guiaSalida, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error inesperado al listar Guias de Salida', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarGuiaSalida',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al listar las Guias de Salida', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarVentasActivas($sede)
    {
        // $filtros = $request->all();
        try {
            $ventas = DB::table('documentoventa as dv')
                ->select([
                    'dv.Codigo',
                    'dv.Serie',
                    'td.Siglas',
                    'dv.Numero',
                    DB::raw("DATE(dv.Fecha) as Fecha"),
                    DB::raw("CASE 
                            WHEN dv.CodigoPersona IS NOT NULL THEN CONCAT(p.Apellidos, ' ', p.Nombres)
                            ELSE ce.RazonSocial
                        END AS Cliente")
                ])
                ->join('tipodocumentoventa as td', 'dv.CodigoTipoDocumentoVenta', '=', 'td.Codigo')
                ->leftJoin('personas as p', 'dv.CodigoPersona', '=', 'p.Codigo')
                ->leftJoin('clienteempresa as ce', 'dv.CodigoClienteEmpresa', '=', 'ce.Codigo')
                ->where('dv.Vigente', 1)
                ->whereDate('dv.Fecha', '>=', '2025-07-21')
                ->where('dv.CodigoSede', $sede)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->fromSub(function ($sub) {
                            $sub->select([
                                'P.Codigo as CodigoProducto',
                                'DDV.Cantidad',
                                'DDV.CodigoVenta',
                            ])
                                ->from('detalledocumentoventa as DDV')
                                ->join('producto as P', 'P.Codigo', '=', 'DDV.CodigoProducto')
                                ->where('P.Tipo', 'B')
                                ->unionAll(
                                    DB::table('detalledocumentoventa as DDV')
                                        ->select([
                                            'P.Codigo as CodigoProducto',
                                            DB::raw('DDV.Cantidad * PC.Cantidad as Cantidad'),
                                            'DDV.CodigoVenta',
                                        ])
                                        ->join('producto as Co', 'Co.Codigo', '=', 'DDV.CodigoProducto')
                                        ->join('productocombo as PC', 'PC.CodigoCombo', '=', 'Co.Codigo')
                                        ->join('producto as P', 'P.Codigo', '=', 'PC.CodigoProducto')
                                        ->where('Co.Tipo', 'C')
                                        ->where('P.Tipo', 'B')
                                );
                        }, 'PrVe')
                        ->leftJoinSub(function ($subEnt) {
                            $subEnt->select([
                                'gs.CodigoVenta',
                                'dgs.CodigoProducto',
                                DB::raw('SUM(dgs.Cantidad) as Cantidad')
                            ])
                                ->from('guiasalida as gs')
                                ->join('detalleguiasalida as dgs', 'dgs.CodigoGuiaSalida', '=', 'gs.Codigo')
                                ->groupBy('gs.CodigoVenta', 'dgs.CodigoProducto');
                        }, 'Ent', function ($join) {
                            $join->on('Ent.CodigoProducto', '=', 'PrVe.CodigoProducto')
                                ->on('Ent.CodigoVenta', '=', 'PrVe.CodigoVenta');
                        })
                        ->whereRaw('PrVe.CodigoVenta = dv.Codigo')
                        ->whereRaw('PrVe.Cantidad > COALESCE(Ent.Cantidad, 0)');
                })
                ->get();

            // Log de éxito
            Log::info('Ventas activas listadas correctamente', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarVentasActivas',
                'cantidad' => count($ventas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($ventas, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al listar Ventas Activas', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarVentasActivas',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al listar las Compras', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarDetalleVenta($venta)
    {
        try {

            $sql = "
                SELECT PrVe.Codigo as CodigoProducto, PrVe.Cantidad - coalesce(Ent.Cantidad, 0) as Cantidad, PrVe.Nombre as Descripcion
                FROM (
                    -- Productos individuales (Tipo = 'B')
                    SELECT P.Codigo, DDV.Cantidad, P.Nombre
                    FROM detalledocumentoventa DDV
                    JOIN producto P ON P.Codigo = DDV.CodigoProducto
                    WHERE DDV.CodigoVenta = ? AND P.Tipo = 'B'

                    UNION

                    -- Productos dentro de combos (Tipo = 'C' desglosado)
                    SELECT P.Codigo, DDV.Cantidad * PC.Cantidad AS Cantidad, P.Nombre
                    FROM detalledocumentoventa DDV
                    JOIN producto Co ON Co.Codigo = DDV.CodigoProducto
                    JOIN productocombo PC ON PC.CodigoCombo = Co.Codigo
                    JOIN producto P ON P.Codigo = PC.CodigoProducto
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
            // Log de éxito
            Log::info('Detalle de venta listado correctamente', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarDetalleVenta',
                'cantidad' => count($resultados),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json($resultados, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error inesperado al listar el detalle de la venta', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'listarDetalleVenta',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al listar el detalle de la venta', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarGuiaSalida(Request $request)
    {
        $guiaData = $request->input('guiaSalida');
        $detalleGuia = $request->input('detalleGuiaSalida');
        $fechaActual = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {

            $guiaSalida = GuiaSalida::create($guiaData);

            foreach ($detalleGuia as $detalle) {

                //Consultar el stock de la sede
                $producto = DB::table('sedeproducto')
                    ->where('CodigoProducto', $detalle['CodigoProducto'])
                    ->where('CodigoSede', $guiaData['CodigoSede'])
                    ->first();

                //Para calcular el nuevo stock del lote
                $stockSede = $producto->Stock ?? 0;
                $costoSede = $producto->CostoCompraPromedio ?? 0;

                //Crear el Detalle Guia Salida
                $detalle['CodigoGuiaSalida'] = $guiaSalida->Codigo;
                $detalle['Costo'] = $costoSede;
                $CodigoDetalle = DetalleGuiaSalida::create($detalle);

                foreach ($detalle['lote'] as $lote) {
                    
                    //Actualizar el stock del lote
                    DB::table('lote')->where('Codigo', $lote['Codigo'])->decrement('Stock', $lote['Cantidad']);

                    // Calcular nuevo stock localmente
                    $stockSede -= $lote['Cantidad']; 

                    //PARA GENERAR MOVIMIENTO LOTE
                    $movimientoLote['CodigoDetalleSalida'] = $CodigoDetalle->Codigo;
                    $movimientoLote['CodigoLote'] = $lote['Codigo'];
                    $movimientoLote['TipoOperacion'] = 'S';
                    $movimientoLote['Fecha'] = $fechaActual;
                    $movimientoLote['Stock'] = $stockSede;
                    $movimientoLote['Cantidad'] = $lote['Cantidad']; // cantidad de salida
                    $movimientoLote['CostoPromedio'] = $costoSede;

                    MovimientoLote::create($movimientoLote);
                }

                //Actualizar el stock de la sede
                DB::table('sedeproducto')
                    ->where('CodigoProducto', $detalle['CodigoProducto'])
                    ->where('CodigoSede', $guiaData['CodigoSede'])
                    ->decrement('Stock', $detalle['Cantidad']);
            }

            DB::commit();
            // Log de éxito
            Log::info('Guia de salida registrada correctamente', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'registrarGuiaSalida',
                'codigo_guia' => $guiaSalida->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['mensaje' => 'Guia de salida registrada'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error general
            Log::error('Error inesperado al registrar Guia de Salida', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'registrarGuiaSalida',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al registrar la guia de salida', 'bd' => $e->getMessage()], 500);
        }
    }


    public function consultarGuiaSalida($codigo){

        try{

            // Primera consulta: cabecera de la guía salida
            $guiaSalida = DB::table('guiasalida as g')
                ->leftJoin('personas as p', 'g.CodigoPaciente', '=', 'p.Codigo')
                ->select(
                    'g.Codigo',
                    'g.TipoDocumento',
                    'g.Serie',
                    'g.Numero',
                    'g.Fecha',
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Paciente"),
                    'g.Vigente'
                )
                ->where('g.Codigo', $codigo)
                ->first();


            // Segunda consulta: detalle de la guía salida
            $detalleGuia = DB::table('detalleguiasalida as dgs')
                ->join('producto as p', 'dgs.CodigoProducto', '=', 'p.Codigo')
                ->select(
                    'dgs.Codigo',
                    'p.Nombre as Descripcion',
                    'dgs.Cantidad'
                )
                ->where('dgs.CodigoGuiaSalida', $codigo)
                ->get();
            
            $guia = [
                'cabecera' => $guiaSalida,
                'detalle' => $detalleGuia
            ];

            return response()->json($guia, 200);
        }catch(\Exception $e){
            Log::error('Error inesperado al consultar guia de salida', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'consultarGuiaSalida',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al consultar la guia de salida', 'bd' => $e->getMessage()], 500);
        }
    }

    public function buscarCorrelativoSalidaDonante($sede, $serie){

        try{

            $numero = ((int) DB::table('guiasalida')
                ->where('TipoDocumento', 'D')
                ->where('CodigoSede', $sede)
                ->where('Serie', $serie)
                ->orderByDesc('Codigo')
                ->value('Numero')) ?: 0;

            $numero = $numero + 1;

            return response()->json($numero, 200);

        }catch(\Exception $e){
            Log::error('Error inesperado al buscar correlativo de salida donante', [
                'Controlador' => 'GuiaSalidaController',
                'Metodo' => 'buscarCorrelativoSalidaDonante',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al buscar el correlativo de salida donante', 'bd' => $e->getMessage()], 500);
        }
    }
}
