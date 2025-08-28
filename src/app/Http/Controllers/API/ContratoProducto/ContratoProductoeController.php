<?php

namespace App\Http\Controllers\API\ContratoProducto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\ContratoProducto\RegistrarContratoProductoRequest;
use App\Http\Requests\Recaudacion\ContratoProducto\RegistrarDetalleContratoRequest;
use App\Models\Recaudacion\AnulacionContrato;
use App\Models\Recaudacion\CompromisoContrato;
use App\Models\Recaudacion\ContratoProducto;
use App\Models\Recaudacion\DetalleContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ContratoProductoeController extends Controller
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

    public function buscarProducto(Request $request)
    {
        $nombreProducto = $request->input('nombreProducto');
        $sede = $request->input('sede');

        try {
            $producto = DB::table('sedeproducto as sp')
                ->select(
                    'p.Codigo',
                    'p.Nombre',
                    'sp.Precio as Monto',
                    'p.Tipo',
                    'tg.Tipo as TipoGravado',
                    'sp.Stock'
                )
                ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->where('sp.CodigoSede', $sede) // Filtro por CódigoSede
                ->where('sp.Vigente', 1) // Filtro por Vigente en sedeproducto
                ->where('p.Vigente', 1) // Filtro por Vigente en producto
                ->where('tg.Vigente', 1) // Filtro por Vigente en tipogravado
                ->where('p.Tipo', '!=', 'C')
                ->where('p.Nombre', 'LIKE', "{$nombreProducto}%") // Filtro por Nombre
                ->get();

            // Log de éxito
            Log::info('Productos encontrados correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'buscarProducto',
                'nombreProducto' => $nombreProducto,
                'Cantidad' => count($producto),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'

            ]);

            return response()->json($producto);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al buscar producto', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'buscarProducto',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Error al buscar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function registrarContratoProducto(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesContrato = $request->input('detalleContrato');
        $contratoProductoData = $request->input('contratoProducto');
        $compromisoContrato = $request->input('compromisoContrato');

        //Validar Contrato
        $contratoValidar = Validator::make($contratoProductoData, (new RegistrarContratoProductoRequest())->rules());
        $contratoValidar->validate();

        //validar DetalleProductos
        $detalleContratoValidar = Validator::make(
            ['detalleContrato' => $detallesContrato],
            (new RegistrarDetalleContratoRequest())->rules()
        );

        $detalleContratoValidar->validate();

        if (isset($contratoProductoData['CodigoAutorizador']) && $contratoProductoData['CodigoAutorizador'] == 0) {
            $contratoProductoData['CodigoAutorizador'] = null;
        }

        if (isset($contratoProductoData['CodigoPaciente']) && $contratoProductoData['CodigoPaciente'] == 0) {
            $contratoProductoData['CodigoPaciente'] = null;
        }

        if (isset($contratoProductoData['CodigoPaciente02']) && $contratoProductoData['CodigoPaciente02'] == 0) {
            $contratoProductoData['CodigoPaciente02'] = null;
        }

        if (isset($contratoProductoData['CodigoClienteEmpresa']) && $contratoProductoData['CodigoClienteEmpresa'] == 0) {
            $contratoProductoData['CodigoClienteEmpresa'] = null;
        }

        // Obtener el CódigoSede desde los datos del contrato
        $codigoSede = $contratoProductoData['CodigoSede'];

        // Obtener el último NumContrato para la sede específica y sumarle 1
        $ultimoNumContrato = ContratoProducto::where('CodigoSede', $codigoSede)
            ->max('NumContrato');
        $contratoProductoData['NumContrato'] = $ultimoNumContrato ? $ultimoNumContrato + 1 : 1;

        $contratoProductoData['Fecha'] = $fecha;

        DB::beginTransaction();
        try {
            // Crear el ContratoProducto
            $Contrato = ContratoProducto::create($contratoProductoData);

            // Crear los DetalleContrato
            foreach ($detallesContrato as $detalle) {
                $detalle['CodigoContrato'] = $Contrato->Codigo;
                DetalleContrato::create($detalle);
            }

            if (!empty($request->input('compromisoContrato')) && count($compromisoContrato) > 0) {
                foreach ($compromisoContrato as $compromiso) {
                    $compromiso['CodigoContrato'] = $Contrato->Codigo;
                    CompromisoContrato::create($compromiso);
                }
            }

            // Confirmar la transacción
            DB::commit();

            // Log de éxito
            Log::info('Contrato registrado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'registrarContratoProducto',
                'NumContrato' => $Contrato->NumContrato,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Contrato registrado correctamente'
            ], 200);
        } catch (\Exception $e) {
            // Hacer rollback en caso de error
            DB::rollBack();

            // Log de error
            Log::error('Error al registrar contrato producto', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'registrarContratoProducto',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Error al registrar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buscarContratoProducto(Request $request)
    {

        $codigoSede = $request->input('codigoSede');
        $nombre = $request->input('nombre');
        $documento = $request->input('documento');
        // ->where(DB::raw("DATE(cp.Fecha)"), $fecha)

        try {
            $contratos = DB::table('contratoproducto as cp')
                ->join('personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                ->where('cp.CodigoSede', $codigoSede) // Filtrar por CódigoSede
                ->when(!empty($nombre), function ($query) use ($nombre) {
                    return $query->where(function ($q) use ($nombre) {
                        $q->where('p.Nombres', 'LIKE', "$nombre%")
                            ->orWhere('p.Apellidos', 'LIKE', "$nombre%");
                    });
                })
                ->when(!empty($documento), function ($query) use ($documento) {
                    return $query->where('p.NumeroDocumento', 'LIKE', "$documento%");
                })

                ->orderByDesc('cp.Codigo')
                ->select(
                    'cp.Codigo as Codigo',
                    DB::raw("DATE(cp.Fecha) as Fecha"),
                    'cp.Total as Total',
                    'cp.TotalPagado as TotalPagado',
                    DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as NombrePaciente"),
                    'cp.Vigente as Vigente'
                )
                ->get();

            // Log de éxito
            Log::info('Contratos encontrados correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'buscarContratoProducto',
                'Cantidad' => count($contratos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($contratos);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al buscar contrato producto', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'buscarContratoProducto',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Error al buscar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function anularContrato(Request $request)
    {
        $codigo = $request->input('codigoContrato');
        $anularContrato = $request->input('anularContrato');

        DB::beginTransaction();
        try {
            // Verificar si hay documentos de venta asociados
            $existeDocumentoVenta = DB::table('documentoventa as dv')
                ->where('dv.CodigoContratoProducto', $codigo)
                ->where('dv.Vigente', 1)
                ->exists();

            // Sumar los montos totales de los documentos de venta
            $sumaMontoTotal = DB::table('documentoventa as dv')
                ->where('dv.CodigoContratoProducto', $codigo)
                ->sum('dv.MontoTotal');

            // Permitir anulación si no hay documentos o si la suma de montos es 0
            if (!$existeDocumentoVenta || $sumaMontoTotal == 0.0) {
                DB::table('contratoproducto')
                    ->where('Codigo', $codigo)
                    ->update(['Vigente' => 0]);

                DB::table('compromisocontrato')
                    ->where('CodigoContrato', $codigo)
                    ->where('Vigente', 1)
                    ->update(['Vigente' => 0]);

                $anularContrato['Codigo'] = $codigo;
                AnulacionContrato::create($anularContrato);

                DB::commit();

                // Log de éxito
                Log::info('Contrato anulado correctamente', [
                    'Controlador' => 'ContratoProductoeController',
                    'Metodo' => 'anularContrato',
                    'CodigoContrato' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'message' => 'Contrato anulado correctamente',
                    'id' => 1
                ], 200);
            } else {
                DB::rollBack();

                // Log de error
                Log::warning('No se puede anular contrato con documentos de venta pendientes', [
                    'Controlador' => 'ContratoProductoeController',
                    'Metodo' => 'anularContrato',
                    'CodigoContrato' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'message' => 'No se puede anular contrato porque tiene documentos de venta con montos pendientes.',
                    'id' => 2
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de error
            Log::error('Error al anular contrato', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'anularContrato',
                'CodigoContrato' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Error al anular contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function visualizarContrato($contrato)
    {

        // $contratoProducto = ContratoProducto::find($contrato);

        try {
            $contratoProducto = DB::table('contratoproducto as cp')
                ->select(
                    'cp.Codigo',
                    'cp.CodigoAutorizador',
                    'cp.CodigoMedico',
                    'cp.CodigoPaciente',
                    'cp.CodigoPaciente02',
                    DB::raw("CONCAT(pPac.Apellidos, ' ', pPac.Nombres) AS Nombre"),
                    DB::raw("CONCAT(td.Nombre, ': ', pPac.NumeroDocumento) AS Documento"),
                    DB::raw("CONCAT(pPac2.Apellidos, ' ', pPac2.Nombres) AS nombreSegundo"),
                    DB::raw("CONCAT(td2.Nombre, ': ', pPac2.NumeroDocumento) AS numDocumentoSegundo"),
                    'cp.Total'
                )
                ->join('personas as pPac', 'pPac.Codigo', '=', 'cp.CodigoPaciente')
                ->leftJoin('personas as pPac2', 'pPac2.Codigo', '=', 'cp.CodigoPaciente02')
                ->join('tipo_documentos as td', 'td.Codigo', '=', 'pPac.CodigoTipoDocumento')
                ->leftJoin('tipo_documentos as td2', 'td2.Codigo', '=', 'pPac2.CodigoTipoDocumento')
                ->where('cp.Codigo', $contrato)
                ->first();


            $detalleContrato = DB::table('detallecontrato as dc')
                ->select('dc.CodigoProducto as Codigo', 'dc.Descripcion as Nombre', 'dc.MontoTotal as SubTotal', 'dc.Cantidad', 'dc.Descuento')
                ->join('producto as p', 'p.Codigo', '=', 'dc.CodigoProducto')
                ->where('dc.CodigoContrato', $contrato)
                ->get();

            // Log de éxito
            Log::info('Contrato visualizado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'visualizarContrato',
                'CodigoContrato' => $contrato,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'contratoProducto' => $contratoProducto,
                'detalleContrato' => $detalleContrato,
            ]);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al visualizar contrato', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'visualizarContrato',
                'CodigoContrato' => $contrato,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Error al buscar contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function historialContratoVenta($codigo)
    {

        try {
            $ventas = DB::table('documentoventa as dv')
                ->selectRaw("
                    dv.Codigo AS Venta,
                    dv.CodigoContratoProducto AS Contrato,
                    CONCAT(tdv.Nombre, ' ', dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) AS Documento,
                    CASE 
                        WHEN e.Codigo IS NOT NULL THEN mpe.Nombre 
                        WHEN p.Codigo IS NOT NULL THEN mp.Nombre 
                    END AS MedioPago,
                    dv.MontoPagado,
                    dv.Vigente AS VentaVigente,
                    DATE(dv.Fecha) AS FechaVenta,
                    CASE 
                        WHEN dv.CodigoMotivoNotaCredito IS NOT NULL THEN 'N' 
                        WHEN dv.Vigente = 0 THEN 'A' 
                        WHEN dv.CodigoMotivoNotaCredito IS NULL THEN 'V' 
                    END AS TipoVenta,
                    tdv.CodigoSUNAT AS CodigoSUNAT,
                    COALESCE(
                        (SELECT dv.MontoTotal + SUM(nc.MontoTotal) 
                        FROM documentoventa AS nc 
                        WHERE nc.CodigoMotivoNotaCredito IS NOT NULL 
                        AND nc.CodigoDocumentoReferencia = dv.Codigo
                        ), 
                        dv.MontoTotal
                    ) AS SaldoFinal
                ")
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
                ->leftJoin('documentoventa as NOTACREDITO', function ($join) {
                    $join->on('NOTACREDITO.CodigoDocumentoReferencia', '=', 'dv.Codigo')
                        ->whereNotNull('NOTACREDITO.CodigoMotivoNotaCredito');
                })
                ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('pago as p', 'p.Codigo', '=', 'pdv.CodigoPago')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
                ->leftJoin('devolucionnotacredito as dnc', 'dnc.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('egreso as e', 'e.Codigo', '=', 'dnc.Codigo')
                ->leftJoin('mediopago as mpe', 'mpe.Codigo', '=', 'e.CodigoMedioPago')
                ->where('dv.CodigoContratoProducto', $codigo)
                ->orderBy('dv.Codigo', 'ASC')
                ->get();

            // Log de éxito
            Log::info('Historial de ventas consultado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'historialContratoVenta',
                'CodigoContrato' => $codigo,
                'CantidadVentas' => count($ventas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($ventas);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al buscar historial de ventas', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'historialContratoVenta',
                'CodigoContrato' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Error al buscar historial de ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function contratoPDF($contrato)
    {
        try {
            $contratoData = DB::table('contratoproducto as c')
                ->join('sedesrec as s', 's.Codigo', '=', 'c.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->join('personas as cliente', 'cliente.Codigo', '=', 'c.CodigoPaciente')
                ->join('personas as medico', 'medico.Codigo', '=', 'c.CodigoMedico')
                ->leftjoin('personas as cliente2', 'cliente2.Codigo', '=', 'c.CodigoPaciente02')
                ->leftjoin('tipo_documentos as td2', 'td2.Codigo', '=', 'cliente2.CodigoTipoDocumento')
                ->join('tipo_documentos as td', 'td.Codigo', '=', 'cliente.CodigoTipoDocumento')
                ->select(
                    'e.Nombre as empresaNombre',
                    'e.RUC as ruc',
                    'e.Direccion as direccion',
                    DB::raw("'Lambayeque' as departamento"),
                    DB::raw("'Chiclayo' as provincia"),
                    DB::raw("'Chiclayo' as distrito"),
                    's.Nombre as sede',
                    DB::raw("LPAD(c.NumContrato, 8, '0') AS numero"),
                    DB::raw("CONCAT(cliente.Apellidos, ' ', cliente.Nombres) as cliente"),
                    'td.Nombre as documentoIdentidad',
                    'cliente.NumeroDocumento as numDocumento',
                    'cliente.Direccion as clienteDireccion',

                    DB::raw("CONCAT(cliente2.Apellidos, ' ', cliente2.Nombres) as cliente2"),
                    'td2.Nombre as documentoIdentidad2',
                    'cliente2.NumeroDocumento as numDocumento2',


                    DB::raw("DATE(c.Fecha) AS fechaEmision"),
                    DB::raw("'Soles' as moneda"),
                    DB::raw("CONCAT(medico.Apellidos, ' ', medico.Nombres) as medico")
                )
                ->where('c.Codigo', $contrato)
                ->first();

            // Obtener detalles del contrato
            $detalleContrato = DB::table('detallecontrato as dc')
                ->select(
                    'dc.Descripcion as descripcion',
                    'dc.MontoTotal as monto',
                    'dc.Cantidad as cantidad',
                    DB::raw('(dc.MontoTotal * dc.Cantidad) as subTotal')
                )
                ->where('dc.CodigoContrato', $contrato)
                ->get();

            // Obtener compromisos de pago del contrato
            $compromisos = DB::table('compromisocontrato')
                ->select('Fecha as fecha', 'Monto as monto')
                ->where('CodigoContrato', $contrato)
                ->get();

            // Log de éxito
            Log::info('Contrato PDF generado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'contratoPDF',
                'CodigoContrato' => $contrato,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return  response()->json([
                'contrato' => $contratoData,
                'detalle' => $detalleContrato,
                'compromisos' => $compromisos
            ]);
        } catch (\Exception $e) {

            // Log de error
            Log::error('Error al generar PDF del contrato', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'contratoPDF',
                'CodigoContrato' => $contrato,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarDetallesPagados($contrato)
    {
        try {

            $detalles = DB::table('documentoventa as dv')
                ->join('detalledocumentoventa as ddv', 'dv.Codigo', '=', 'ddv.CodigoVenta')
                ->join('detallecontrato as dc', 'ddv.CodigoDetalleContrato', '=', 'dc.Codigo')
                ->join('producto as p', 'ddv.CodigoProducto', '=', 'p.Codigo')
                ->select(
                    'dc.Codigo as detalleContrato',
                    'p.Nombre as Producto',
                    'p.Codigo as CodigoProducto',
                    DB::raw('SUM(ddv.MontoTotal) as DetalleMonto')
                )
                ->where('dv.CodigoContratoProducto', $contrato)
                ->where('dv.Vigente', 1)
                ->groupBy('dc.Codigo', 'p.Nombre', 'p.Codigo')
                ->get();

            // Log de éxito
            Log::info('Detalles pagados listados correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'listarDetallesPagados',
                'CantidadDetalles' => count($detalles),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($detalles, 200);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al listar detalles pagados', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'listarDetallesPagados',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Error al listar detalles pagados',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function cambioContratoProducto(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesContrato = $request->input('detalleContrato');
        $contratoProductoData = $request->input('contratoProducto');
        $compromisoContrato = $request->input('compromisoContrato');

        //Validar Contrato
        $contratoValidar = Validator::make($contratoProductoData, (new RegistrarContratoProductoRequest())->rules());
        $contratoValidar->validate();

        //validar DetalleProductos
        $detalleContratoValidar = Validator::make(
            ['detalleContrato' => $detallesContrato],
            (new RegistrarDetalleContratoRequest())->rules()
        );

        $detalleContratoValidar->validate();

        $contratoOriginal = $contratoProductoData['Codigo']; //Contrato original codigo

        $contratoProductoData['Codigo'] = null; //Para crear el nuevo contrato

        if (isset($contratoProductoData['CodigoAutorizador']) && $contratoProductoData['CodigoAutorizador'] == 0) {
            $contratoProductoData['CodigoAutorizador'] = null;
        }

        if (isset($contratoProductoData['CodigoPaciente']) && $contratoProductoData['CodigoPaciente'] == 0) {
            $contratoProductoData['CodigoPaciente'] = null;
        }

        if (isset($contratoProductoData['CodigoPaciente02']) && $contratoProductoData['CodigoPaciente02'] == 0) {
            $contratoProductoData['CodigoPaciente02'] = null;
        }

        if (isset($contratoProductoData['CodigoClienteEmpresa']) && $contratoProductoData['CodigoClienteEmpresa'] == 0) {
            $contratoProductoData['CodigoClienteEmpresa'] = null;
        }

        // Obtener el CódigoSede desde los datos del contrato
        $codigoSede = $contratoProductoData['CodigoSede'];

        // Obtener el último NumContrato para la sede específica y sumarle 1
        $ultimoNumContrato = ContratoProducto::where('CodigoSede', $codigoSede)->max('NumContrato');
        $contratoProductoData['NumContrato'] = $ultimoNumContrato ? $ultimoNumContrato + 1 : 1;
        $contratoProductoData['Fecha'] = $fecha;

        DB::beginTransaction();
        try {
            // Crear el ContratoProducto
            $Contrato = ContratoProducto::create($contratoProductoData);

            $sumaMontoDetalles = 0;
            // Crear los DetalleContrato
            foreach ($detallesContrato as $detalle) {
                $detalle['CodigoContrato'] = $Contrato->Codigo;
                // Crear el detalle del contrato
                $detalleContratoCreado = DetalleContrato::create($detalle);

                if (isset($detalle['detallePagado']) && isset($detalle['detallePagado']) != null) {

                    $detalleContratoId = $detalle['detallePagado']['detalleContrato'] ?? null;
                    $sumaMontoDetalles += $detalle['detallePagado']['montoPagado'] ?? 0;
                    // Primero obtener los códigos
                    $documentosIds = DB::table('documentoventa as dv')
                        ->join('detalledocumentoventa as ddv', 'dv.Codigo', '=', 'ddv.CodigoVenta')
                        ->where('dv.CodigoContratoProducto', $contratoOriginal)
                        ->where('dv.Vigente', 1)
                        ->where('ddv.CodigoDetalleContrato', $detalleContratoId)
                        ->pluck('dv.Codigo')
                        ->toArray();

                    // Luego actualizar
                    if (!empty($documentosIds)) {
                        DB::table('documentoventa')
                            ->whereIn('Codigo', $documentosIds)
                            ->update(['CodigoContratoProducto' => $Contrato->Codigo]);

                        DB::table('detalledocumentoventa')
                            ->whereIn('CodigoVenta', $documentosIds)
                            ->where('CodigoDetalleContrato', $detalleContratoId)
                            ->update(['CodigoDetalleContrato' => $detalleContratoCreado->Codigo]);
                    }
                }
            }

            // Actualizar el monto total del contrato
            if ($sumaMontoDetalles > 0) {
                DB::table('contratoproducto')
                    ->where('Codigo', $Contrato->Codigo)
                    ->update(['TotalPagado' => $sumaMontoDetalles]);
            }

            if (!empty($request->input('compromisoContrato')) && count($compromisoContrato) > 0) {
                foreach ($compromisoContrato as $compromiso) {
                    $compromiso['CodigoContrato'] = $Contrato->Codigo;
                    CompromisoContrato::create($compromiso);
                }
            }

            // Verificar si el contrato original existe
            $contratoOriginal = ContratoProducto::find($contratoOriginal);
            if (!$contratoOriginal) {
                // Log especial para el caso de no encontrar el contrato original
                Log::warning('Contrato original no encontrado', [
                    'Controlador' => 'ContratoProductoeController',
                    'Metodo' => 'cambioContratoProducto',
                    'CodigoContratoOriginal' => $contratoOriginal,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'message' => 'Contrato original no encontrado'
                ], 404);
            }
            // Actualizar contrato Original Vigente = 0 
            $contratoOriginal->Vigente = 0;
            $contratoOriginal->save();
            // Confirmar la transacción
            DB::commit();

            // Log de éxito
            Log::info('Cambio de contrato registrado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'cambioContratoProducto',
                'NumContrato' => $Contrato->NumContrato,
                'CodigoContratoOriginal' => $contratoOriginal,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Cambio de contrato registrado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            // Hacer rollback en caso de error
            DB::rollBack();

            // Log de error
            Log::error('Error al cambiar el contrato producto', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'cambioContratoProducto',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Error al cambiar el contrato.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
