<?php

namespace App\Http\Controllers\API\PagosVarios;

use App\Helpers\ValidarEgreso;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagosVarios;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PagosVariosController extends Controller
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

    public function actualizarPagoVarios(Request $request)
    {
        $servicio = $request->input('pagosVarios');
        $egreso = $request->input('egreso');
        $egreso['CodigoSUNAT'] = '008';
        DB::beginTransaction();
        try {

            // Buscar registros en BD
            $egresoData = isset($egreso['Codigo']) ? Egreso::find($egreso['Codigo']) : null;
            $servicioData = PagosVarios::find($servicio['Codigo']);

            if ($egresoData) {
                $egreso = ValidarEgreso::validar($egreso, $servicio);
                $estadoCaja = ValidarFecha::obtenerFechaCaja($egresoData->CodigoCaja);

                // Caja cerrada -> solo servicio (sin monto)
                if ($estadoCaja->Estado == 'C') {

                    if($egreso['Vigente'] == 0){
                        
                        return response()->json([
                            'error' => 'Error al actualizar el pago varios.',
                            'message' => 'Error al actualizar el pago varios, la caja ya fue cerrada.'
                        ], 500);

                    }

                    $servicioFiltrado = collect($servicio)
                        ->except(['Monto', 'Vigente'])
                        ->toArray();
                    $servicioData->update($servicioFiltrado);

                    DB::commit();
                    return response()->json('Pago varios actualizado con éxito.', 200);
                
                }
                // Caja abierta -> actualizar todo
                $servicioData->update($servicio);
                $egresoData->update($egreso);
            }


            DB::commit();
            //log info
            Log::info('Pago Varios actualizado correctamente', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'actualizarPagoVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoEgreso' => $egreso['Codigo'],
            ]);

            return response()->json('Pago varios actualizado correctamente.', 200);

        } catch (\Throwable $e) {
             DB::rollBack();

            // Detectar si el error es SQL
            $isSqlError = $e instanceof \Illuminate\Database\QueryException;

            // Guardar el mensaje técnico (para logs, no para el usuario)
            $errorInterno = $e->getMessage();

            // Mensaje público seguro
            $mensajePublico = $isSqlError
                ? 'Ocurrió un error en la base de datos. Por favor, contacte al administrador.'
                : ($errorInterno ?: 'Error desconocido.');

            // Registrar el error técnico en el log de Laravel

            //log error
            Log::error('Error al actualizar Pago Varios', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'actualizarPagoVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoEgreso' => $egreso['Codigo'],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                 'error' => $errorInterno,
            ]);

            return response()->json(['error' => 'Error al actualizar el pago varios.', 'message' => $mensajePublico], 500);
        }
    }


    public function registrarPagoVarios(Request $request)
    {
        $pagoVarios = $request->input('pagosVarios');
        $egreso = $request->input('egreso');
        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();

        $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

        if ($egreso['Monto'] > $total) {
            //log warning
            Log::warning(
                'Intento de registrar un pago varios con monto mayor al efectivo disponible',
                [
                    'Controlador' => 'PagosVariosController',
                    'Metodo' => 'registrarPagoVarios',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'CodigoCaja' => $egreso['CodigoCaja'],
                    'MontoEgreso' => $egreso['Monto'],
                    'TotalCaja' => $total,
                ]
            );
            return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            //log warning
            Log::warning('Intento de registrar un pago varios con fecha posterior a la fecha de
            caja', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'registrarPagoVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoCaja' => $egreso['CodigoCaja'],
                'FechaCaja' => $fechaCajaVal,
                'FechaEgreso' => $fechaVentaVal,
            ]);

            return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
        }


        DB::beginTransaction();
        try {

            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoVarios['Codigo'] = $idEgreso;
            PagosVarios::create($pagoVarios);

            DB::commit();

            //log info
            Log::info('Pago Varios registrado correctamente', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'registrarPagoVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoEgreso' => $idEgreso,
            ]);
            return response()->json(['message' => 'Pago Varios registrado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al registrar Pago Varios', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'registrarPagoVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoEgreso' => $idEgreso ?? null,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => 'Error al registrar el pago', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarPagosVarios(Request $request)
    {

        $sede = $request->input('CodigoSede');
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $tipo = $request->input('tipo');

        try {
            $pagosVarios = DB::table('pagosvarios as pv')
                ->join('egreso as e', 'e.Codigo', '=', 'pv.Codigo')
                ->join('personas as p', 'p.Codigo', '=', 'pv.CodigoReceptor')
                ->join('caja as c', 'c.Codigo', '=', 'e.CodigoCaja')
                ->selectRaw('e.Codigo, DATE(e.Fecha) as Fecha, pv.Tipo, e.Monto, pv.Comentario, e.Vigente, CONCAT(p.Apellidos, " ", p.Nombres) as Receptor')
                // ->where('e.Vigente', 1)
                ->where('c.CodigoSede', $sede)
                ->when(!empty($tipo), function ($query) use ($tipo) {
                    return $query->where('pv.Tipo', $tipo);
                })
                ->when(!empty($fechaInicio), function ($query) use ($fechaInicio) {
                    return $query->whereDate('e.Fecha', '>=', $fechaInicio);
                })
                ->when(!empty($fechaFin), function ($query) use ($fechaFin) {
                    return $query->whereDate('e.Fecha', '<=', $fechaFin);
                })
                ->orderByDesc('e.Fecha')
                ->get();
            //log info
            Log::info('Listado de Pagos Varios', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'listarPagosVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $pagosVarios->count(),
            ]);
            return response()->json($pagosVarios, 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al listar Pagos Varios', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'listarPagosVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => 'Error al listar los pagos varios', 'message' => $e->getMessage()], 500);
        }
    }

    public function consultarPagosVarios($codigo)
    {
        try {
            // Obtener datos de pagosvarios
            $pagosVarios = DB::table('pagosvarios')
                ->select('Codigo','CodigoReceptor', 'Tipo', 'Comentario', 'Motivo', 'Destino')
                ->where('Codigo', $codigo)
                ->first(); // Usamos first() para obtener un solo resultado

            // Obtener datos de egreso
            $egreso = Egreso::join('caja as c', 'egreso.CodigoCaja', '=', 'c.Codigo')
                ->select('egreso.*', 'c.Estado as EstadoCaja')
                ->where('egreso.Codigo', $codigo)
                ->first();

            //log info
            Log::info('Consulta de Pagos Varios', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'consultarPagosVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $codigo,
            ]);

            // Retornar la respuesta en JSON
            return response()->json([
                'pagosVarios' => $pagosVarios,
                'egreso' => $egreso
            ]);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al consultar Pagos Varios', [
                'Controlador' => 'PagosVariosController',
                'Metodo' => 'consultarPagosVarios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => 'Error al consultar los pagos varios', 'message' => $e->getMessage()], 500);
        }
    }
}
