<?php

namespace App\Http\Controllers\API\ReportesRecaudacion;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
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

    public function listarProducto($sede)
    {
        // $sede = $request->input('sede');
        // $nombre = $request->input('nombre');
        try {
            $productos = DB::table('sedeproducto as sp')
                ->select(
                    'p.Codigo',
                    'p.Nombre',
                )
                ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->where('sp.CodigoSede', $sede) // Filtro por CódigoSede
                ->where('p.Tipo', 'B') // Filtro por Tipo = 'B'
                ->where('p.Vigente', 1) // Filtro por Vigente en producto
                // ->where('p.Nombre', 'LIKE', "{$nombre}%") // Filtro por Nombre
                ->get();


            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los productos', $e], 500);
        }
    }

    public function empleados(){
        try{
            $trabajadores = DB::table('trabajadors as t')
            ->join('personas as p', 't.Codigo', '=', 'p.Codigo')
            ->where('t.Tipo', 'A')
            ->select('p.Codigo', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres"))
            ->get();        
            return response()->json($trabajadores, 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al listar los Trabajadores', 'bd' => $e->getMessage()], 400);
        }
    }

    public function empresas(){
        try{
            $empresas = DB::table('empresas')
                ->select('Codigo', 'RazonSocial')
                ->get();
            return response()->json($empresas, 200); 
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al listar las Empresas', 'bd' => $e->getMessage()], 400);
        }
    }

    public function sedes(){
        try{
            $sedes = DB::table('sedesrec')
                ->select('Codigo', 'Nombre')
                ->get();
            return response()->json($sedes, 200); 
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al listar las Sedes', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteCierreCajaEmpleado(Request $request){

        $trabajador = request()->input('CodigoEmpleado'); // Opcional
        $fecha = request()->input('Fecha'); // Opcional
        $sede = request()->input('CodigoSede'); // Opcional

        try{

            $query1 = DB::table('pago as p')
                ->selectRaw("
                    CONCAT(tdv.Siglas, ' ', dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) AS Documento,
                    CONCAT(pa.Nombres, ' ', pa.Apellidos) AS Paciente,
                    mp.Nombre AS MedioPago,
                    CONCAT(DATE_FORMAT(p.Fecha, '%d/%m/%Y'), ' ', TIME(p.Fecha)) AS FechaPago,
                    p.Monto AS MontoPagado,
                    pdv.Vigente AS Vigente,
                    mp.CodigoSUNAT AS CodigoSUNAT
                ")
                ->join('caja as c', 'p.CodigoCaja', '=', 'c.Codigo')
                ->join('pagodocumentoventa as pdv', 'pdv.CodigoPago', '=', 'p.Codigo')
                ->join('documentoventa as dv', 'dv.Codigo', '=', 'pdv.CodigoDocumentoVenta')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
                ->join('personas as pa', 'pa.Codigo', '=', 'dv.CodigoPaciente')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
                ->whereNull('dv.CodigoMotivoNotaCredito') // Equivalente a `IS NULL`
                ->when($fecha, function ($query) use ($fecha) {
                    return $query->whereRaw("DATE(p.Fecha) = ?", [$fecha]);
                })
                ->when($trabajador, function ($query) use ($trabajador) {
                    return $query->where('p.CodigoTrabajador', $trabajador);
                })
                ->when($sede, function ($query) use ($sede) {
                    return $query->where('c.CodigoSede', $sede);
                });
    
                $query2 = DB::table('ingresodinero as i')
                    ->selectRaw("
                        CASE 
                            WHEN i.Tipo = 'A' THEN 'INGRESO APERTURA' 
                            ELSE 'INGRESO' 
                        END AS Documento,
                        ' ' AS Paciente,
                        (SELECT Nombre FROM MedioPago WHERE CodigoSUNAT = '008') AS MedioPago,
                        CONCAT(DATE_FORMAT(i.Fecha, '%d/%m/%Y'), ' ', TIME(i.Fecha)) AS FechaPago,
                        i.Monto AS MontoPagado,
                        i.Vigente AS Vigente,
                        '008' AS CodigoSUNAT
                    ")
                    ->join('caja as c', 'c.Codigo', '=', 'i.CodigoCaja')
                    ->when($trabajador, function ($query) use ($trabajador) {
                        return $query->where('c.CodigoTrabajador', $trabajador);
                    })
                    ->when($fecha, function ($query) use ($fecha) {
                        return $query->whereRaw("DATE(i.Fecha) = ?", [$fecha]);
                    })
                    ->when($sede, function ($query) use ($sede) {
                        return $query->where('c.CodigoSede', $sede);
                    });
    
                $Egresos = DB::table('Egreso as e')
                ->selectRaw("
                    CASE
                        WHEN ps.Codigo IS NOT NULL THEN 'PAGO DE SERVICIOS'
                        WHEN pp.Codigo IS NOT NULL THEN 'PAGO A PROVEEDOR'
                        WHEN sd.Codigo IS NOT NULL THEN 'SALIDA DE DINERO'
                        WHEN dnc.Codigo IS NOT NULL THEN 'DEVOLUCIÓN NOTA CRÉDITO'
                        WHEN pd.Codigo IS NOT NULL THEN 'PAGO DONANTE'
                        WHEN pc.Codigo IS NOT NULL THEN 'PAGO COMISIÓN'
                        WHEN pper.Codigo IS NOT NULL THEN 'PAGO PERSONAL'
                        WHEN pvar.Codigo IS NOT NULL THEN 'PAGO VARIOS'
                        WHEN pdet.Codigo IS NOT NULL THEN 'PAGO DETRACCION'
                        ELSE 'OTRO'
                    END AS Detalle,
                    CONCAT(DATE_FORMAT(e.Fecha, '%d/%m/%Y'), ' ', TIME(e.Fecha)) AS Fecha,
                    e.Monto AS Monto,
                    mp.Nombre AS MedioPago,
                    mp.CodigoSUNAT as CodigoSUNAT
                ")
                ->leftJoin('pagoservicio AS ps', 'ps.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagoproveedor as pp', 'pp.Codigo', '=', 'e.Codigo')
                ->leftJoin('salidadinero AS sd', 'sd.Codigo', '=', 'e.Codigo')
                ->leftJoin('devolucionnotacredito as dnc', 'dnc.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagoDonante as pd', 'pd.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagoComision as pc', 'pc.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagopersonal as pper', 'pper.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagosvarios as pvar', 'pvar.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagodetraccion as pdet', 'pdet.Codigo', '=', 'e.Codigo')
                ->leftJoin('medioPago as mp', 'mp.Codigo', '=', 'e.CodigoMedioPago')
                ->leftJoin('caja as c', 'e.CodigoCaja', '=', 'c.Codigo')
                // Aplicar filtros opcionales
                ->when($trabajador, fn($query) => $query->where('e.CodigoTrabajador', $trabajador))
                ->when($fecha, fn($query) => $query->whereRaw("DATE(e.Fecha) = ?", [$fecha]))
                ->when($sede, fn($query) => $query->where('c.CodigoSede', $sede))
                // Obtener resultados
                ->get();
    
                $Ingresos = $query1
                ->unionAll($query2)
                ->orderBy('FechaPago', 'desc') // Ordena por FechaPago en orden descendente
                ->get();
    
            return response()->json(['Ingresos' => $Ingresos, 'Egresos' => $Egresos], 200);
    
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al listar los ingresos pendientes', 'error' => $e->getMessage()], 400);
        }
    }


    public function reporteIngresosPeriodoEmpresa(){

        $anio = request()->input('anio'); // Opcional
        $mes = request()->input('mes'); // Opcional
        $empresa = request()->input('empresa'); // Opcional

        try{

            $query = DB::table('documentoventa as dv')
                ->selectRaw("
                    tdv.Nombre as Documento,
                    dv.Serie as Serie,
                    LPAD(dv.Numero, 9, '0') as Numero,
                    CONCAT(DATE_FORMAT(dv.Fecha, '%d/%m/%Y'), ' ', TIME(dv.Fecha)) AS Fecha,
                    CASE 
                        WHEN dv.CodigoClienteEmpresa IS NULL THEN p.NumeroDocumento
                        ELSE ce.RUC
                    END AS NumDocumento,
                    CASE 
                        WHEN dv.CodigoClienteEmpresa IS NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                        ELSE ce.RazonSocial
                    END AS Cliente,
                    dv.TotalGravado as BaseTributaria,
                    dv.IGVTotal as IGV,
                    dv.MontoTotal as Monto,
                    dv.Vigente as Vigente
                ")
                ->join('tipodocumentoventa as tdv', 'dv.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')
                ->join('sedesrec as s', 'dv.CodigoSede', '=', 's.Codigo')
                ->join('empresas as e', 's.CodigoEmpresa', '=', 'e.Codigo')
                ->leftJoin('personas as p', 'dv.CodigoPersona', '=', 'p.Codigo')
                ->leftJoin('clienteempresa as ce', 'dv.CodigoClienteEmpresa', '=', 'ce.Codigo')
                ->whereNull('dv.CodigoMotivoNotaCredito')
                ->when($anio && $mes, function ($query) use ($anio, $mes) {
                    return $query->whereYear('dv.Fecha', $anio)->whereMonth('dv.Fecha', $mes);
                })
                ->when($empresa, function ($query) use ($empresa) {
                    return $query->where('e.Codigo', $empresa);
                })
                ->get();

            return response()->json($query, 200);
        
        }catch(\Exception $e){
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteKardexSimple(){

        $fechaActual = date('Y-m-d');
        $codigoProducto = request()->input('producto'); // Requerido
        $fechaIncio = request()->input('fechaInicio'); // Opcional
        $fechaFin = request()->input('fechaFin') ?? $fechaActual; // Opcional

        try{
            $datos = DB::table('movimientolote AS ml')
            ->join('lote AS l', 'l.Codigo', '=', 'ml.CodigoLote')
            ->select(
                'l.Serie',
                'ml.Fecha',
                DB::raw("
                    CASE
                        WHEN ml.TipoOperacion = 'I' THEN 'Ingreso'
                        WHEN ml.TipoOperacion = 'S' THEN 'Salida'
                        ELSE 'Otros'
                    END AS TipoOperacion
                "),
                'l.Cantidad',
                'ml.Stock'
            )
            ->where('l.CodigoProducto', $codigoProducto)
            ->whereBetween('ml.Fecha', [$fechaIncio, $fechaFin]) // 🔥 Filtro de fechas
            ->get();

            return response()->json($datos, 200);

        }catch(\Exception $e){
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteKardexValorizado(){

        $fechaActual = date('Y-m-d');
        $codigoProducto = request()->input('producto'); // Requerido
        $fechaIncio = request()->input('fechaInicio'); // Opcional
        $fechaFin = request()->input('fechaFin') ?? $fechaActual; // Opcional

        try{

            $datos = DB::table('movimientolote AS ml')
            ->join('lote AS l', 'l.Codigo', '=', 'ml.CodigoLote')
            ->select(
                'l.Serie',
                'ml.Fecha',
                DB::raw("
                    CASE
                        WHEN ml.TipoOperacion = 'I' THEN 'Ingreso'
                        WHEN ml.TipoOperacion = 'S' THEN 'Salida'
                        ELSE 'Otros'
                    END AS TipoOperacion
                "),
                DB::raw('l.Stock / l.Costo AS Inversion'),
                'l.Cantidad',
                'ml.Stock',
                'ml.CostoPromedio'
            )
            ->where('l.CodigoProducto', $codigoProducto)
            ->whereBetween('ml.Fecha', [$fechaIncio, $fechaFin]) // 🔥 Filtro de fechas
            ->get();

            return response()->json($datos, 200);

        }catch(\Exception $e){
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteProductosPorVencer(){
        $fecha = request()->input('fecha');
    
        // Calcular fecha fin (30 días después)
        $fechaActual = Carbon::now()->toDateString(); // '2025-04-07' por ejemplo
    
        try {
            $productos = DB::table('producto AS p')
            ->join('lote AS l', 'p.Codigo', '=', 'l.CodigoProducto')
            ->select(
                'l.Serie',
                'p.Nombre',
                'l.Cantidad',
                'l.Stock',
                'l.FechaCaducidad',
                DB::raw("DATEDIFF(l.FechaCaducidad, ?) AS DiasPorVencer")
            )
            ->where('l.FechaCaducidad', $fecha)
            ->where('l.Stock', '>', 0) // Solo productos con cantidad mayor a 0
            ->addBinding([$fechaActual], 'select') // Pasa la fecha actual como parámetro para DATEDIFF
            ->get();
    
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteCatalogoProductos(Request $request){
        
        $categoria = request()->input('Fecha'); // Opcional
        $sede = request()->input('CodigoSede'); // Opcional

        try{

            $productos = DB::table('sedeproducto as sp')
            ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
            ->join('categoriaproducto as cp', 'p.CodigoCategoria', '=', 'cp.Codigo')
            ->select('p.Nombre as Producto', 'cp.Nombre as Categoria')
            ->where('p.Tipo', 'B')
            ->when($sede, fn($query) => $query->where('sp.CodigoSede', $sede))
            ->when($categoria, fn($query) => $query->where('cp.Codigo', $categoria))
            ->get();

            // ->when($sede, function ($query) use ($sede) {
            //     return $query->where('c.CodigoSede', $sede);
            // });

            return response()->json($productos, 200);

        }catch(\Exception $e){
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }
}
