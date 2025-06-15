<?php

namespace App\Http\Controllers\API\Pago;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Pago\RegistrarPagoRequest;
use App\Models\Recaudacion\Pago;
use App\Models\Recaudacion\PagoDocumentoVenta;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
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


    // ---------------------------------------------------------------------------------------
    public function registrarPago(RegistrarPagoRequest $request)
    {
        // Verificación de los datos
        if ($request['CodigoMedioPago'] == 1) {
            $request['CodigoCuentaBancaria'] = null;
            $request['NumeroOperacion'] = null;
        }

        try {
            // Intentar crear el pago
            Pago::create($request->all());
            Log::info('Registrar Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'registrarPago',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Pago registrado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'registrarPago',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
            ]);
            // En caso de error, devuelve el mensaje de error y un código de estado 500 (error interno del servidor)
            return response()->json(['message' => 'Error al registrar el Pago', 'error' => $e->getMessage()], 500);
        }
    }


    public function registrarPagoDocumentoVenta(Request $request)
    {

        $pagoDocData = $request->input('pagoDocVenta');
        DB::beginTransaction();

        try {
            DB::table('pagodocumentoventa')->insert([
                'CodigoPago' => $pagoDocData['CodigoPago'],
                'CodigoDocumentoVenta' =>  $pagoDocData['CodigoDocumentoVenta'],
                'Monto' => $pagoDocData['Monto']
            ]);

            DB::table('documentoventa')
                ->where('Codigo', $pagoDocData['CodigoDocumentoVenta'])
                ->increment('MontoPagado', $pagoDocData['Monto']);

            DB::commit();

            Log::info('Registrar Pago Documento Venta', [
                'Controlador' => 'PagoController',
                'Metodo' => 'registrarPagoDocumentoVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Pago Asociado correctamente.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al asociar Pago Documento Venta', [
                'Controlador' => 'PagoController',
                'Metodo' => 'registrarPagoDocumentoVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'Error al asociar el Pago.',
                'bd' => $e->getMessage(),
            ], 500);
        }
    }


    // ---------------------------------------------------------------------------------------


    public function buscarPago(Request $request)
    {

        $codigoSede = $request->input('CodigoSede');

        try {
            $subquery = DB::table('pagodocumentoventa as pdv')
                ->join('documentoventa as d', 'd.Codigo', '=', 'pdv.CodigoDocumentoVenta')
                ->where('pdv.Vigente', 1)
                ->where('d.CodigoSede', $codigoSede)
                ->groupBy('pdv.CodigoPago')
                ->select('pdv.CodigoPago', DB::raw('SUM(pdv.Monto) AS MontoAsociado'));

            $pagos = DB::table('pago as p')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
                ->join('caja as CAJA', 'CAJA.Codigo', '=', 'p.CodigoCaja')
                ->leftJoinSub($subquery, 'PAG', 'PAG.CodigoPago', '=', 'p.Codigo')
                ->where('p.Vigente', 1)
                ->where('CAJA.CodigoSede', $codigoSede)
                ->whereRaw('p.Monto > COALESCE(PAG.MontoAsociado, 0)')
                ->selectRaw("
                    p.Codigo,
                    CASE 
                        WHEN mp.CodigoSUNAT = '008' THEN '-'
                        WHEN mp.CodigoSUNAT IN ('005','006') THEN CONCAT(p.Lote, ' - ', p.Referencia)
                        ELSE p.NumeroOperacion
                    END AS NumeroOperacion,
                    mp.Nombre,
                    DATE(p.Fecha) AS Fecha,
                    p.Monto,
                    COALESCE(PAG.MontoAsociado, 0) AS MontoAsociado,
                    (p.Monto - COALESCE(PAG.MontoAsociado, 0)) AS MontoPorAsociar
                ")
                ->get();

            //log info
            Log::info('Buscar Pagos', [
                'Controlador' => 'PagoController',
                'Metodo' => 'buscarPago',
                'Contador' => count($pagos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($pagos, 200);
        } catch (\Exception $e) {
            Log::error('Error al buscar Pagos', [
                'Controlador' => 'PagoController',
                'Metodo' => 'buscarPago',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function buscarVentas(Request $request)
    {
        // $fecha = $request->input('fecha');
        $codigoSede = $request->input('CodigoSede');
        // $pago = $request->input('pago');

        try {

            $subquery = "
                SELECT CodigoDocumentoReferencia, SUM(MontoTotal) AS MontoTotalNC
                FROM documentoventa 
                WHERE CodigoMotivoNotaCredito IS NOT NULL
                AND Vigente = 1
                AND CodigoSede = $codigoSede
                GROUP BY CodigoDocumentoReferencia
            ";

            $ventas = DB::table('documentoventa as VENTA')
                ->leftJoin(DB::raw("($subquery) AS NOTACREDITO"), 'NOTACREDITO.CodigoDocumentoReferencia', '=', 'VENTA.Codigo')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'VENTA.CodigoTipoDocumentoVenta')
                ->whereNull('VENTA.CodigoMotivoNotaCredito')
                ->where('VENTA.Vigente', 1) // Se pasa como valor directo
                ->where('VENTA.CodigoSede', $codigoSede) // Se pasa como valor directo
                ->whereRaw('(VENTA.MontoTotal - VENTA.MontoPagado + COALESCE(NOTACREDITO.MontoTotalNC, 0)) > 0')
                ->orderByDesc('VENTA.Codigo')
                ->selectRaw('
                VENTA.Codigo, 
                VENTA.MontoPagado, 
                VENTA.MontoTotal, 
                (VENTA.MontoTotal - VENTA.MontoPagado + COALESCE(NOTACREDITO.MontoTotalNC, 0)) AS Saldo,
                tdv.Nombre, 
                VENTA.Serie, 
                VENTA.Numero, 
                DATE(VENTA.Fecha) AS Fecha
            ')
                ->get();
            //log info
            Log::info('Buscar Ventas', [
                'Controlador' => 'PagoController',
                'Metodo' => 'buscarVentas',
                'Contador' => count($ventas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json($ventas, 200);
        } catch (\Exception $e) {

            Log::error('Error al buscar Ventas', [
                'Controlador' => 'PagoController',
                'Metodo' => 'buscarVentas',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function anularPago(Request $request)
    {
        $codigoPago = $request->input('codigoPago');
        $codigoTrabajador = $request->input('codigoTrabajador');
        $codigoCaja = $request->input('codigoCaja');

        DB::beginTransaction();
        try {

            //Verificar si pertenece a  su caja

            $registroPagoExiste = Pago::find($codigoPago); //Encontro resultado

            $estadoCaja = ValidarFecha::obtenerFechaCaja($registroPagoExiste->CodigoCaja); // Caja que registra el pago

            if ($estadoCaja->Estado == 'C') {
                // log warning
                Log::warning('Intento de anular Pago en caja cerrada', [
                    'Controlador' => 'PagoController',
                    'Metodo' => 'anularPago',
                    'codigoPago' => $codigoPago,
                    'codigoCaja' => $codigoCaja,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'error' => __('mensajes.error_act_egreso_caja', ['tipo' => '']),
                ], 400);
            }


            if ($registroPagoExiste) {
                // Actualizar 'pago' si existe
                DB::table('pago')
                    ->where('Codigo', $codigoPago)
                    ->update([
                        'CodigoTrabajador' => $codigoTrabajador,
                        'Vigente' => 0
                    ]);
            } else {
                DB::rollBack();
                // log warning
                Log::warning('Intento de anular Pago no existente', [
                    'Controlador' => 'PagoController',
                    'Metodo' => 'anularPago',
                    'codigoPago' => $codigoPago,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'message' => 'El Pago no existe.',
                ], 404);
            }

            DB::commit();
            // log info
            Log::info('Anular Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'anularPago',
                'codigoPago' => $codigoPago,
                'codigoTrabajador' => $codigoTrabajador,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Pago anulado correctamente.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // log error
            Log::error('Error al anular Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'anularPago',
                'codigoPago' => $codigoPago,
                'codigoTrabajador' => $codigoTrabajador,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json([
                'message' => 'Error al anular el Pago.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function consultarPago(Request $request)
    {
        $codigoPago = $request->input('codigoPago');

        try {

            $pago = Pago::find($codigoPago); //Encontro resultado
            if (!$pago) {
                // log warning
                Log::warning('Pago no encontrado', [
                    'Controlador' => 'PagoController',
                    'Metodo' => 'consultarPago',
                    'codigoPago' => $codigoPago,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'error' => 'No se ha encontrado el pago.'
                ], 404);
            }

            // log info
            Log::info('Consultar Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'consultarPago',
                'codigoPago' => $codigoPago,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($pago, 200);
        } catch (\Exception $e) {
            // log error
            Log::error('Error al consultar Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'consultarPago',
                'codigoPago' => $codigoPago,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function editarPago(Request $request)
    {
        $pagoData = $request->input('pago'); //Viene del front

        $pagoEncontrado = Pago::find($pagoData['Codigo']); //Encontro resultado
        $estadoCaja = ValidarFecha::obtenerFechaCaja($pagoEncontrado->CodigoCaja); // Caja Actual


        if (!$pagoEncontrado) {
            // log warning
            Log::warning('Pago no encontrado', [
                'Controlador' => 'PagoController',
                'Metodo' => 'editarPago',
                'codigoPago' => $pagoData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'error' => 'No se ha encontrado el pago.'
            ], 404);
        }

        // if($pagoEncontrado->CodigoCaja != $pagoData['CodigoCaja']){
        //     return response()->json([
        //         'error' => __('mensajes.error_act_egreso_caja', ['tipo' => '']),
        //     ], 400);
        // }

        if ($estadoCaja->Estado == 'C') {
            // log warning
            Log::warning('Intento de editar Pago en caja cerrada', [
                'Controlador' => 'PagoController',
                'Metodo' => 'editarPago',
                'codigoPago' => $pagoData['Codigo'],
                'codigoCaja' => $pagoData['CodigoCaja'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'error' => __('mensajes.error_act_egreso_caja', ['tipo' => '']),
            ], 400);
        }

        if (isset($pagoData['CodigoCuentaBancaria']) && $pagoData['CodigoCuentaBancaria'] == 0) {
            $pagoData['CodigoCuentaBancaria'] = null;
        }

        if (isset($pagoData['CodigoBilleteraDigital']) && $pagoData['CodigoBilleteraDigital'] == 0) {
            $pagoData['CodigoBilleteraDigital'] = null;
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($pagoData['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($pagoData['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            // log warning
            Log::warning(
                'Intento de editar Pago con fecha fuera del rango de la caja',
                [
                    'Controlador' => 'PagoController',
                    'Metodo' => 'editarPago',
                    'codigoPago' => $pagoData['Codigo'],
                    'fechaCaja' => $fechaCajaVal,
                    'fechaVenta' => $fechaVentaVal,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]
            );
            return response()->json([
                'error' => __('mensajes.error_fecha_recaudacion'),
            ], 400);
        }

        if ($pagoData['CodigoSUNAT'] == '008') {
            $pagoData['CodigoCuentaBancaria'] = null;
            $pagoData['CodigoBilleteraDigital'] = null;
            $pagoData['Lote'] = null;
            $pagoData['Referencia'] = null;
            $pagoData['NumeroOperacion'] = null;
        } else if ($pagoData['CodigoSUNAT'] == '003') {
            $pagoData['Lote'] = null;
            $pagoData['Referencia'] = null;
        } else if ($pagoData['CodigoSUNAT'] == '005' || $pagoData['CodigoSUNAT'] == '006') {
            $pagoData['CodigoCuentaBancaria'] = null;
            $pagoData['CodigoBilleteraDigital'] = null;
        }


        try {


            DB::table('pago')
                ->where('Codigo', $pagoData['Codigo'])
                ->update([
                    'CodigoMedioPago' => $pagoData['CodigoMedioPago'],
                    'CodigoCuentaBancaria' => $pagoData['CodigoCuentaBancaria'],
                    'NumeroOperacion' => $pagoData['NumeroOperacion'],
                    'Fecha' => $pagoData['Fecha'],
                    'Monto' => $pagoData['Monto'],
                    'CodigoBilleteraDigital' => $pagoData['CodigoBilleteraDigital'],
                    'CodigoTrabajador' => $pagoData['CodigoTrabajador'],
                    'Lote' => $pagoData['Lote'],
                    'Referencia' => $pagoData['Referencia'],
                    'NumeroOperacion' => $pagoData['NumeroOperacion'],
                ]);

            // log info
            Log::info('Editar Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'editarPago',
                'codigoPago' => $pagoData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Pago actualizado correctamente',
            ], 200);
            
        } catch (\Exception $e) {
            // log error
            Log::error('Error al editar Pago', [
                'Controlador' => 'PagoController',
                'Metodo' => 'editarPago',
                'codigoPago' => $pagoData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
