<?php

namespace App\Http\Controllers\API\PagoServicio;

use App\Helpers\ValidarEgreso;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\MotivoPagoServicio;
use App\Models\Recaudacion\PagoServicio;
use App\Models\Recaudacion\SalidaDinero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Http\Requests\Recaudacion\PagoServicio\RegistrarPagoServicioRequest;
use App\Models\Recaudacion\Servicio;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class PagoServicioController extends Controller
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

    public function consultarPagoServicio($codigo)
    {
        $egreso = null;
        try {

            $servicio = DB::table('servicio as s')
                ->select([
                    's.Codigo',
                    's.CodigoMotivoPago',
                    's.CodigoProveedor',
                    's.TipoDocumento',
                    's.Serie',
                    's.Numero',
                    's.FechaDocumento',
                    's.Descripcion',
                    's.Monto',
                    's.IGV',
                    's.Vigente',
                    DB::raw('IFNULL(ps.Codigo, 0) as pago')
                ])
                ->leftJoin('pagoservicio as ps', 's.Codigo', '=', 'ps.CodigoServicio')
                ->where('s.Codigo', $codigo)
                ->first();

            
                if (!empty($servicio->pago) && $servicio->pago != 0) {
                    $egreso = Egreso::join('caja as c', 'egreso.CodigoCaja', '=', 'c.Codigo')
                        ->select('egreso.*', 'c.Estado as EstadoCaja')
                        ->where('egreso.Codigo', $servicio->pago)
                        ->first();
                }

                return response()->json([
                    'servicio' => $servicio,
                    'egreso' => $egreso
                ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al consultar el pago del servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listarPagos(Request $request)
    {
        $fecha = $request->input('fecha');
        $sede = $request->input('sede');

        try {
        $results = DB::table('servicio as s')
            ->select([
                's.Codigo as Codigo',
                DB::raw("DATE_FORMAT(s.FechaDocumento, '%d/%m/%Y') as FechaDocumento"),
                's.TipoDocumento',
                DB::raw("CONCAT(s.Serie, ' - ', s.Numero) as Documento"),
                'p.RazonSocial',
                'mps.Nombre as Motivo',
                DB::raw("IFNULL(mp.Nombre, '-') as MedioPago"),
                's.Monto',
                's.Vigente',
                DB::raw("
                    CASE
                        WHEN e.Codigo IS NULL THEN 'Por pagar'
                        ELSE 'Pagado'
                    END as EstadoPago
                "),
                's.Vigente as Vigente',
            ])
            ->join('motivopagoservicio as mps', 's.CodigoMotivoPago', '=', 'mps.Codigo')
            ->join('proveedor as p', 's.CodigoProveedor', '=', 'p.Codigo')
            ->leftJoin('pagoservicio as ps', 's.Codigo', '=', 'ps.CodigoServicio')
            ->leftJoin('egreso as e', 'ps.Codigo', '=', 'e.Codigo')
            ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'e.CodigoMedioPago')
            ->where('s.CodigoSede', $sede) // <-- aquí usas tu variable $sede
            ->get();

            return response()->json([
                'pagos' => $results
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar los pagos del servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarPago(Request $request)
    {

        $servicio = $request->input('servicio');
        $egreso = $request->input('egreso');

        //Validar PagoServicio
        $pagoServicioValidator = Validator::make($servicio, (new RegistrarPagoServicioRequest())->rules());
        $pagoServicioValidator->validate();

        if($egreso){
            
            //Validar Egreso
            $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();

            $egreso = ValidarEgreso::validar($egreso, $servicio);

            // $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
            // $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
            // $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon
            // $fechaPagoVal = Carbon::parse($servicio['FechaDocumento'])->toDateString(); // Convertir el string a Carbon

            // if ($fechaCajaVal < $fechaVentaVal) {
            //     return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
            // }
            // if ($fechaCajaVal < $fechaPagoVal) {
            //     return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
            // }

            // if (isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0) {
            //     $egreso['CodigoCuentaOrigen'] = null;
            // }

            // if (isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0) {
            //     $egreso['CodigoBilleteraDigital'] = null;
            // }

            // if ($egreso['CodigoSUNAT'] == '008') {
            //     $egreso['CodigoCuentaOrigen'] = null;
            //     $egreso['CodigoBilleteraDigital'] = null;
            //     $egreso['Lote'] = null;
            //     $egreso['Referencia'] = null;
            //     $egreso['NumeroOperacion'] = null;

            //     $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

            //     if ($egreso['Monto'] > $total) {
            //         return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
            //     }
            // } else if ($egreso['CodigoSUNAT'] == '003') {
            //     $egreso['Lote'] = null;
            //     $egreso['Referencia'] = null;
            // } else if ($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006') {
            //     $egreso['CodigoCuentaBancaria'] = null;
            //     $egreso['CodigoBilleteraDigital'] = null;
            // }
        }


        DB::beginTransaction();
        try {
            
            $idServicio = Servicio::create($servicio)->Codigo;

            if($egreso){
                $DataEgreso = Egreso::create($egreso);
                $idEgreso = $DataEgreso->Codigo;

                $pagoServicio['Codigo'] = $idEgreso;
                $pagoServicio['CodigoServicio'] = $idServicio;
                PagoServicio::create($pagoServicio);
            }

            DB::commit();
            return response()->json([
                'message' => 'Pago del servicio registrado correctamente'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al registrar el pago del servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarPago(Request $request)
    {
        $servicio = $request->input('servicio');
        $egreso = $request->input('egreso');

        DB::beginTransaction();
        try {
            // Buscar registros en BD
            $egresoData = isset($egreso['Codigo']) ? Egreso::find($egreso['Codigo']) : null;
            $servicioData = Servicio::find($servicio['Codigo']);

            // Verificar si hay egreso
            if ($egresoData) {

                $egreso = ValidarEgreso::validar($egreso, $servicio);

                $estadoCaja = ValidarFecha::obtenerFechaCaja($egresoData->CodigoCaja);

                // Caja cerrada -> solo servicio (sin monto)
                if ($estadoCaja->Estado == 'C') {

                    if($servicio['Vigente'] == 0){
                        
                        return response()->json([
                            'error' => 'Error al actualizar el pago del servicio',
                            'message' => 'Error al actualizar el pago del servicio, la caja ya fue cerrada.'
                        ], 500);

                    }

                    $servicioFiltrado = collect($servicio)
                        ->except(['Monto', 'Vigente'])
                        ->toArray();
                    $servicioData->update($servicioFiltrado);

                    DB::commit();
                    return response()->json('Servicio actualizado con éxito.', 200);
                }

                if($servicio['Vigente'] == 0){
                    $egreso['Vigente'] = 0;
                }

                // Caja abierta -> actualizar todo
                $servicioData->update($servicio);
                $egresoData->update($egreso);

            } else {
                // Sin egreso -> actualizar solo servicio
                if ($egreso) {
                    $egreso = ValidarEgreso::validar($egreso, $servicio);

                    $idNuevoEgreso = Egreso::create($egreso)->Codigo;

                    $pagoServicio = [
                        'Codigo' => $idNuevoEgreso,
                        'CodigoServicio' => $servicio['Codigo']
                    ];
                    PagoServicio::create($pagoServicio);
                }

                $servicioData->update($servicio);
            }

            DB::commit();
            return response()->json('Pago del servicio actualizado correctamente', 200);

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

            Log::error('Error al actualizar el pago del servicio', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarMedioPago',
                 'error' => $errorInterno,
                'data' => [
                    'servicio' => $servicio ?? null,
                    'egreso' => $egreso ?? null,
                ],
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'error' => 'Error al actualizar el pago del servicio',
                'message' => $mensajePublico
            ], 500);
        }
    }


}
