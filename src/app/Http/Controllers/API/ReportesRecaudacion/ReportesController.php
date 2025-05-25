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

    public function empleados($sede)
    {
        try {
            $trabajadores = DB::table('trabajadors as t')
                ->join('personas as p', 't.Codigo', '=', 'p.Codigo')
                ->join('asignacion_sedes as ass', 't.Codigo', '=', 'ass.CodigoTrabajador')
                ->where('t.Tipo', 'A')
                ->where('ass.CodigoSede', $sede)
                ->select('p.Codigo', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres"))
                ->distinct()
                ->get();
            return response()->json($trabajadores, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los Trabajadores', 'bd' => $e->getMessage()], 400);
        }
    }

    public function medicos($sede)
    {
        try {
            $trabajadores = DB::table('trabajadors as t')
                ->join('personas as p', 't.Codigo', '=', 'p.Codigo')
                ->join('asignacion_sedes as ass', 't.Codigo', '=', 'ass.CodigoTrabajador')
                ->where('t.Tipo', 'M')
                ->where('ass.CodigoSede', $sede)
                ->select('p.Codigo', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres"))
                ->distinct()
                ->get();
            return response()->json($trabajadores, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los Médicos', 'bd' => $e->getMessage()], 400);
        }
    }

    public function listarProveedorPagos($sede)
    {
        try {

            $proveedores = DB::table('egreso as e')
                ->selectRaw("
                    DISTINCT
                    CASE 
                        WHEN ps.Codigo IS NULL THEN p1.Codigo
                        ELSE p2.Codigo
                    END AS Codigo,
                    CASE 
                        WHEN ps.Codigo IS NULL THEN p1.RazonSocial
                        ELSE p2.RazonSocial
                    END AS Nombre
                ")
                ->leftJoin('pagoproveedor as pp', 'e.Codigo', '=', 'pp.Codigo')
                ->leftJoin('pagoservicio as ps', 'e.Codigo', '=', 'ps.Codigo')
                ->leftJoin('proveedor as p1', 'pp.CodigoProveedor', '=', 'p1.Codigo')
                ->leftJoin('proveedor as p2', 'ps.CodigoProveedor', '=', 'p2.Codigo')
                ->leftJoin('caja as c', 'e.CodigoCaja', '=', 'c.Codigo')
                ->where('c.CodigoSede', $sede)
                ->where('e.Vigente', 1)
                ->get();
            return response()->json($proveedores, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los Proveedores', 'bd' => $e->getMessage()], 400);
        }
    }

    public function listarProveedoresPendientes($sede)
    {
        try {

            $proveedores = DB::table('compra as c')
                ->distinct()
                ->select('p.Codigo', 'p.RazonSocial')
                ->join('proveedor as p', 'c.CodigoProveedor', '=', 'p.Codigo')
                ->join('cuota as cu', 'c.Codigo', '=', 'cu.CodigoCompra')
                ->leftJoin('pagoproveedor as pp', 'cu.Codigo', '=', 'pp.CodigoCuota')
                ->whereNull('pp.Codigo')
                ->where('c.CodigoSede', $sede)
                ->where('c.Vigente', 1)
                ->get();

            return response()->json($proveedores, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los Proveedores', 'bd' => $e->getMessage()], 400);
        }
    }

    public function empresas()
    {
        try {
            $empresas = DB::table('empresas')
                ->select('Codigo', 'RazonSocial')
                ->where('Vigente', 1)
                ->get();
            return response()->json($empresas, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las Empresas', 'bd' => $e->getMessage()], 400);
        }
    }

    public function sedes($empresa = null)
    {
        try {
            $sedes = DB::table('sedesrec')
                ->select('Codigo', 'Nombre')
                ->where('Vigente', 1)
                ->when(!is_null($empresa) && $empresa != 0, function ($query) use ($empresa) {
                    return $query->where('CodigoEmpresa', $empresa);
                })
                ->get();

            return response()->json($sedes, 200);
            return response()->json($sedes, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las Sedes', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteCierreCajaEmpleado(Request $request)
    {

        $trabajador = request()->input('CodigoEmpleado'); // Opcional
        $fecha = request()->input('Fecha'); // Opcional
        $sede = request()->input('CodigoSede'); // Opcional

        try {

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
                        (SELECT Nombre FROM mediopago WHERE CodigoSUNAT = '008') AS MedioPago,
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

            $Egresos = DB::table('egreso as e')
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
                    mp.Nombre AS MedioPago,
                    SUM(e.Monto) AS TotalMonto
                ")
                ->leftJoin('pagoservicio AS ps', 'ps.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagoproveedor as pp', 'pp.Codigo', '=', 'e.Codigo')
                ->leftJoin('salidadinero AS sd', 'sd.Codigo', '=', 'e.Codigo')
                ->leftJoin('devolucionnotacredito as dnc', 'dnc.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagodonante as pd', 'pd.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagocomision as pc', 'pc.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagopersonal as pper', 'pper.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagosvarios as pvar', 'pvar.Codigo', '=', 'e.Codigo')
                ->leftJoin('pagodetraccion as pdet', 'pdet.Codigo', '=', 'e.Codigo')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'e.CodigoMedioPago')
                ->leftJoin('caja as c', 'e.CodigoCaja', '=', 'c.Codigo')
                // Aplicar filtros opcionales
                ->where('mp.CodigoSUNAT', '008')
                ->where('e.Vigente', 1)
                ->when($trabajador, fn($query) => $query->where('e.CodigoTrabajador', $trabajador))
                ->when($fecha, fn($query) => $query->whereRaw("DATE(e.Fecha) = ?", [$fecha]))
                ->when($sede, fn($query) => $query->where('c.CodigoSede', $sede))
                // Obtener resultados
                ->groupBy('Detalle', 'mp.Nombre')
                ->orderBy('Detalle')
                ->get();

            $Ingresos = $query1
                ->unionAll($query2)
                ->orderBy('FechaPago', 'desc') // Ordena por FechaPago en orden descendente
                ->get();

            return response()->json(['Ingresos' => $Ingresos, 'Egresos' => $Egresos], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los ingresos pendientes', 'error' => $e->getMessage()], 400);
        }
    }


    public function reporteIngresosPeriodoEmpresa()
    {

        $anio = request()->input('anio'); // Opcional
        $mes = request()->input('mes'); // Opcional
        $empresa = request()->input('empresa'); // Opcional

        try {

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
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteProductosReabastecer()
    {

        $sede = request()->input('Sede'); // Opcional

        try {
            $productos = DB::table('sedeproducto as sp')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->join('categoriaproducto as c', 'p.CodigoCategoria', '=', 'c.Codigo')
                ->select(
                    'p.Nombre as Producto',
                    'sp.Stock',
                    'sp.StockMinimo'
                )
                ->where('sp.CodigoSede', $sede)
                ->where('p.Tipo', 'B')
                ->where('sp.Controlado', 1)
                ->whereColumn('sp.Stock', '<=', 'sp.StockMinimo')
                ->get();

            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteKardexSimple()
    {

        $fechaActual = date('Y-m-d');
        $codigoProducto = request()->input('producto'); // Requerido
        $fechaIncio = request()->input('fechaInicio'); // Opcional
        $fechaFin = request()->input('fechaFin') ?? $fechaActual; // Opcional
        $sede = request()->input('sede'); // Opcional

        try {
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
                ->where('l.CodigoSede', $sede) // Filtro por CódigoSede
                ->whereBetween('ml.Fecha', [$fechaIncio, $fechaFin]) // 🔥 Filtro de fechas
                ->get();

            return response()->json($datos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteKardexValorizado()
    {

        $fechaActual = date('Y-m-d');
        $codigoProducto = request()->input('producto'); // Requerido
        $fechaIncio = request()->input('fechaInicio'); // Opcional
        $fechaFin = request()->input('fechaFin') ?? $fechaActual; // Opcional
        $sede = request()->input('sede'); // Opcional

        try {

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
                ->where('l.CodigoSede', $sede) // Filtro por CódigoSede
                ->whereBetween('ml.Fecha', [$fechaIncio, $fechaFin]) // 🔥 Filtro de fechas
                ->get();

            return response()->json($datos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteProductosPorVencer()
    {
        $fecha = request()->input('fecha');
        $sede = request()->input('Sede');

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
                ->when($sede, fn($query) => $query->where('l.CodigoSede', $sede))
                ->addBinding([$fechaActual], 'select') // Pasa la fecha actual como parámetro para DATEDIFF
                ->get();

            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteCatalogoProductos(Request $request)
    {

        $categoria = request()->input('Categoria'); // Opcional
        $sede = request()->input('Sede'); // Opcional
        $nombre = request()->input('Nombre'); // Opcional

        try {

            $productos = DB::table('sedeproducto as sp')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->join('categoriaproducto as cp', 'p.CodigoCategoria', '=', 'cp.Codigo')
                ->select('p.Nombre as Producto', 'cp.Nombre as Categoria', 'sp.Stock')
                ->where('p.Tipo', 'B')
                ->when($sede, fn($query) => $query->where('sp.CodigoSede', $sede))
                ->when($categoria, fn($query) => $query->where('cp.Codigo', $categoria))
                ->when($nombre, fn($query) => $query->where('p.Nombre', 'LIKE', "{$nombre}%"))
                ->get();

            // ->when($sede, function ($query) use ($sede) {
            //     return $query->where('c.CodigoSede', $sede);
            // });

            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el reporte.', 'bd' => $e->getMessage()], 400);
        }
    }

    public function reporteComisionesPendientesPago(Request $request)
    {

        $sede = request()->input('Sede'); // Opcional
        $medico = request()->input('Medico'); // Opcional

        try {
            $comisiones = DB::table('comision as c')
                ->leftJoin('pagocomision as pc', 'c.CodigoPagoComision', '=', 'pc.Codigo')
                ->leftJoin('documentoventa as dv', 'c.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('contratoproducto as cp', 'c.CodigoContrato', '=', 'cp.Codigo')
                ->leftJoin('personas as p', 'c.CodigoMedico', '=', 'p.Codigo')
                ->select(
                    'c.Codigo',
                    DB::raw('p.Codigo AS CodigoMedico'),
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Medico"),
                    'c.TipoDocumento as TipoDocumento',
                    'c.Monto',
                    'c.Serie',
                    'c.Numero',
                    'c.Vigente'
                )
                ->whereNull('c.CodigoPagoComision')
                ->when($medico, function ($query) use ($medico) {
                    $query->where('c.CodigoMedico', $medico);
                })
                ->where('c.Vigente', 1)
                ->where(function ($query) use ($sede) {
                    $query->where(function ($q) use ($sede) {
                        $q->where('dv.CodigoSede', $sede)
                            ->whereNotNull('dv.Codigo');
                    })->orWhere(function ($q) use ($sede) {
                        $q->where('cp.CodigoSede', $sede)
                            ->whereNotNull('cp.Codigo');
                    });
                })
                ->get();

            return response()->json($comisiones, 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al listar las comisiones por pagar.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function reporteContrato_X_Medico(Request $request)
    {

        $sede = request()->input('Sede'); // Opcional
        $medico = request()->input('Medico'); // Opcional

        try {

            $contratos = DB::table('contratoproducto as cp')
                ->join('personas as p', 'cp.CodigoPaciente', '=', 'p.Codigo')
                ->select(
                    DB::raw('DATE(cp.Fecha) as Fecha'),
                    DB::raw("LPAD(cp.NumContrato, 5, '0') as Numero"),
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Paciente01"),
                    'cp.Total',
                    'cp.TotalPagado'
                )
                ->when($medico, function ($query) use ($medico) {
                    $query->where('cp.CodigoMedico', $medico);
                })
                ->where('cp.CodigoSede', $sede) // Asegúrate de tener definido $sede
                ->where('cp.Vigente', 1)
                ->get();

            return response()->json($contratos, 200);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Error al listar los Contratos por Médico.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function reportePagosProveedores(Request $request)
    {
        $sede = $request->input('Sede');
        $proveedor = $request->input('Proveedor');
        $fechaInicio = $request->input('FechaInicio');
        $fechaFin = $request->input('FechaFin');

        try {
            $query = DB::table('egreso as e')
                ->selectRaw("
                DATE(e.Fecha) as Fecha,
                CASE 
                    WHEN ps.Codigo IS NULL THEN 'Compra'
                    ELSE 'Servicio'
                END as TipoEgreso,
                COALESCE(p1.RazonSocial, p2.RazonSocial) as Proveedor,
                e.Monto
            ")
                ->leftJoin('pagoproveedor as pp', 'e.Codigo', '=', 'pp.Codigo')
                ->leftJoin('pagoservicio as ps', 'e.Codigo', '=', 'ps.Codigo')
                ->leftJoin('proveedor as p1', 'pp.CodigoProveedor', '=', 'p1.Codigo')
                ->leftJoin('proveedor as p2', 'ps.CodigoProveedor', '=', 'p2.Codigo')
                ->leftJoin('caja as c', 'e.CodigoCaja', '=', 'c.Codigo')
                ->where('e.Vigente', 1);

            if ($sede) {
                $query->where('c.CodigoSede', $sede);
            }

            if ($proveedor) {
                $query->where(function ($q) use ($proveedor) {
                    $q->where('p1.Codigo', $proveedor)
                        ->orWhere('p2.Codigo', $proveedor);
                });
            }

            if ($fechaInicio && $fechaFin) {
                $query->whereRaw("DATE(e.Fecha) BETWEEN ? AND ?", [$fechaInicio, $fechaFin]);
            }

            $resultados = $query->get();

            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar los pagos a proveedores.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function reportePagosPendientesProveedores(Request $request)
    {
        $sede = $request->input('Sede');
        $proveedor = $request->input('Proveedor');
        try {
            $resultados = DB::table('compra as c')
                ->select([
                    'c.Codigo as Compra',
                    DB::raw('SUM(cu.Monto) as MontoPendiente'),
                    'p.RazonSocial as Proveedor'
                ])
                ->join('proveedor as p', 'c.CodigoProveedor', '=', 'p.Codigo')
                ->join('cuota as cu', 'c.Codigo', '=', 'cu.CodigoCompra')
                ->leftJoin('pagoproveedor as pp', 'cu.Codigo', '=', 'pp.CodigoCuota')
                ->whereNull('pp.Codigo')
                ->where('c.CodigoSede', $sede)
                ->where('c.Vigente', 1)
                // ->where('p.Codigo', 3)
                ->when($proveedor, fn($query) => $query->where('p.Codigo', $proveedor))
                ->groupBy('c.Codigo')
                ->get();

            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar los pagos pendientes a proveedores.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function reporteComisionesMedicoPeriodo(Request $request)
    {
        try {

            $medico = $request->input('Medico'); // puede ser null
            $fechaInicio = $request->input('FechaInicio');
            $fechaFin = $request->input('FechaFin');
            $sede = $request->input('Sede'); // valor por defecto si no se pasa

            $query = DB::table('egreso as e')
                ->selectRaw("
                    DATE(e.Fecha) as Fecha,
                    CONCAT(c.Serie, ' ', c.Numero) as Documento,
                    CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres,
                    c.Comentario,
                    e.Monto
                ")
                ->join('pagocomision as pc', 'e.Codigo', '=', 'pc.Codigo')
                ->join('comision as c', 'pc.Codigo', '=', 'c.CodigoPagoComision')
                ->join('caja as cj', 'e.CodigoCaja', '=', 'cj.Codigo')
                ->join('personas as p', 'c.CodigoMedico', '=', 'p.Codigo')
                ->where('cj.CodigoSede', $sede)
                ->whereBetween(DB::raw('DATE(e.Fecha)'), [$fechaInicio, $fechaFin]);

            if (!is_null($medico) && $medico != 0) {
                $query->where('pc.CodigoMedico', $medico);
            }

            $resultados = $query->get();
            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar las comisiones por pagar.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function reporteIngresoMedico(Request $request)
    {
        try {
            $medico = $request->input('Medico');         // opcional
            $sede = $request->input('Sede');             // obligatorio
            $fechaInicio = $request->input('FechaInicio'); // formato YYYY-MM-DD
            $fechaFin = $request->input('FechaFin');       // formato YYYY-MM-DD

            $query = DB::table('egreso as e')
                ->selectRaw('
                    pc.CodigoMedico,
                    CONCAT(p.Nombres, " ", p.Apellidos) as Nombres,
                    SUM(e.Monto) as PagoTotal
                ')
                ->join('pagocomision as pc', 'e.Codigo', '=', 'pc.Codigo')
                ->join('caja as cj', 'e.CodigoCaja', '=', 'cj.Codigo')
                ->join('personas as p', 'pc.CodigoMedico', '=', 'p.Codigo')
                ->where('e.Vigente', 1)
                ->where('cj.CodigoSede', $sede)
                ->whereBetween(DB::raw('DATE(e.Fecha)'), [$fechaInicio, $fechaFin]);

            if (!is_null($medico) && $medico != 0) {
                $query->where('pc.CodigoMedico', $medico);
            }

            $resultados = $query
                ->groupBy('pc.CodigoMedico', DB::raw('CONCAT(p.Nombres, " ", p.Apellidos)'))
                ->get();
            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al listar los ingresos por médico.',
                'bd' => $e->getMessage()
            ], 500);
        }
    }
}
