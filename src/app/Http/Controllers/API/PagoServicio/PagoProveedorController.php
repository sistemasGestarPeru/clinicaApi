<?php

namespace App\Http\Controllers\API\PagoServicio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Http\Requests\Recaudacion\PagoProveedor\PagoProveedorRequest as GuardarPagoProveedorRequest;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\PagoProveedor;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PagoProveedorController extends Controller
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


    public function registrarPago(Request $request)
    {
        $pagoProveedor = $request->input('pagoProveedor');
        $egreso = $request->input('egreso');

        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();

        //Validar Pago Proveedor
        $pagoProveedorValidator = Validator::make($pagoProveedor, (new GuardarPagoProveedorRequest())->rules());
        $pagoProveedorValidator->validate();

        if (isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0) {
            $egreso['CodigoCuentaOrigen'] = null;
        }

        if (isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0) {
            $egreso['CodigoBilleteraDigital'] = null;
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            //log warning
            Log::warning('Fecha de pago es anterior a la fecha de caja', [
                'FechaCaja' => $fechaCajaVal,
                'FechaPago' => $fechaVentaVal,
                'CodigoCaja' => $egreso['CodigoCaja'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
        }

        if ($egreso['CodigoSUNAT'] == '008') {
            $egreso['CodigoCuentaOrigen'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
            $egreso['NumeroOperacion'] = null;

            $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

            if ($egreso['Monto'] > $total) {
                //log warning
                Log::warning('Pago excede el total disponible en caja', [
                    'MontoPago' => $egreso['Monto'],
                    'TotalDisponible' => $total,
                    'CodigoCaja' => $egreso['CodigoCaja'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
            }
        } else if ($egreso['CodigoSUNAT'] == '003') {
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
        } else if ($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006') {
            $egreso['CodigoCuentaBancaria'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
        }

        DB::beginTransaction();
        try {
            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoProveedor['Codigo'] = $idEgreso;
            PagoProveedor::create($pagoProveedor);

            DB::commit();

            Log::info('Pago registrado correctamente', [
                'Controlador' => 'PagoProveedorController',
                'Metodo' => 'registrarPago',
                'CodigoEgreso' => $idEgreso,
                'CodigoPagoProveedor' => $pagoProveedor['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Pago registrado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al registrar pago', [
                'Controlador' => 'PagoProveedorController',
                'Metodo' => 'registrarPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e], 500);
        }
    }


    public function listarComprasProveedores(Request $request)
    {
        try {

            $resultado = DB::table('compra as C')
                ->join('cuota as CU', 'CU.CodigoCompra', '=', 'C.Codigo')
                ->leftJoin('pagoproveedor as PP', 'PP.CodigoCuota', '=', 'CU.Codigo')
                ->join('proveedor as P', 'P.Codigo', '=', 'C.CodigoProveedor')
                ->select(
                    'C.Codigo as Compra',
                    'C.Fecha',
                    'C.Serie',
                    'C.Numero',
                    'P.Codigo as Proveedor',
                    'P.RazonSocial'
                )
                ->where('CU.Vigente', 1)
                ->where('C.Vigente', 1)
                ->where('P.Vigente', 1)
                ->groupBy(
                    'C.Codigo',
                    'C.Fecha',
                    'C.Serie',
                    'C.Numero',
                    'P.Codigo',
                    'P.RazonSocial'
                )

                ->havingRaw('SUM(CASE WHEN PP.Codigo IS NULL THEN 1 ELSE 0 END) > 0')
                ->orderBy('C.Fecha', 'DESC')
                ->get();

            Log::info('Listado de compras de proveedores', [
                'Controlador' => 'PagoProveedorController',
                'Metodo' => 'listarComprasProveedores',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $resultado->count()
            ]);

            return response()->json($resultado, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarCuotasProveedor(Request $request)
    {
        $codigoCompra = $request->input('codigoCompra');

        try {
            $resultado = DB::table('cuota as C')
                ->select(
                    'C.Codigo',
                    'C.Fecha',
                    'C.Monto',
                    DB::raw("
                        CASE 
                            WHEN tm.Siglas = 'PEN' 
                                THEN COALESCE(SUM(CASE WHEN e.Vigente = 1 THEN e.Monto ELSE 0 END), 0)
                            ELSE COALESCE(SUM(CASE WHEN e.Vigente = 1 THEN PP.MontoMonedaExtranjera ELSE 0 END), 0)
                        END AS MontoTotalPagado
                    "),
                    DB::raw("
                        CASE 
                            WHEN tm.Siglas = 'PEN' 
                                THEN (C.Monto - COALESCE(SUM(CASE WHEN e.Vigente = 1 THEN e.Monto ELSE 0 END), 0))
                            ELSE (C.Monto - COALESCE(SUM(CASE WHEN e.Vigente = 1 THEN PP.MontoMonedaExtranjera ELSE 0 END), 0))
                        END AS MontoRestante
                    "),
                    DB::raw("MAX(PP.Adelanto) as Adelanto"),
                    'C.TipoMoneda as CodMoneda',
                    'tm.Siglas as TipoMoneda',
                    DB::raw("IFNULL(e.NumeroOperacion, ' - ') as NumeroOperacion"),
                    'mp.Nombre as medioPago'
                )
                ->leftJoin('pagoproveedor as PP', 'C.Codigo', '=', 'PP.CodigoCuota')
                ->leftJoin('egreso as e', 'e.Codigo', '=', 'PP.Codigo')
                ->leftJoin('tipomoneda as tm', 'C.TipoMoneda', '=', 'tm.Codigo')
                ->leftJoin('mediopago as mp', 'e.CodigoMedioPago', '=', 'mp.Codigo')
                ->where(function ($query) use ($codigoCompra) {
                    $query->where('C.CodigoCompra', $codigoCompra)
                        ->orWhere('C.CodigoCompra', function ($sub) use ($codigoCompra) {
                            $sub->select('Codigo')
                                ->from('compra')
                                ->where('CodigoDocumentoReferencia', $codigoCompra)
                                ->limit(1);
                        });
                })
                
                ->groupBy('C.Codigo', 'C.Monto', 'C.Fecha', 'C.TipoMoneda', 'tm.Siglas','e.NumeroOperacion','mp.Nombre')
                ->get();

            Log::info('Listado de cuotas de proveedor', [
                'Controlador' => 'PagoProveedorController',
                'Metodo' => 'listarCuotasProveedor',
                'CodigoCompra' => $codigoCompra,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $resultado->count()
            ]);

            return response()->json($resultado, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function pagarCuota(Request $request)
    {

        $pagoProveedor = $request->input('pagoProveedor');
        $egreso = $request->input('egreso');

        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            //log warning
            Log::warning('Fecha de pago es anterior a la fecha de caja', [
                'FechaCaja' => $fechaCajaVal,
                'FechaPago' => $fechaVentaVal,
                'CodigoCaja' => $egreso['CodigoCaja'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
        }

        if (isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0) {
            $egreso['CodigoCuentaOrigen'] = null;
        }

        if (isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0) {
            $egreso['CodigoBilleteraDigital'] = null;
        }

        if ($egreso['CodigoSUNAT'] == '008') {
            $egreso['CodigoCuentaOrigen'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
            $egreso['NumeroOperacion'] = null;

            $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

            if ($egreso['Monto'] > $total) {
                //log warning
                Log::warning('Pago excede el total disponible en caja', [
                    'MontoPago' => $egreso['Monto'],
                    'TotalDisponible' => $total,
                    'CodigoCaja' => $egreso['CodigoCaja'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
            }
        } else if ($egreso['CodigoSUNAT'] == '003') {
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
        } else if ($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006') {
            $egreso['CodigoCuentaBancaria'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
        }

        DB::beginTransaction();

        try {

            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoProveedor['Codigo'] = $idEgreso;

            //agregar pago proveedor    
            DB::commit();
            //log info
            Log::info('Pago de cuota registrado', [
                'Controlador' => 'PagoProveedorController',
                'Metodo' => 'pagarCuota',
                'CodigoEgreso' => $idEgreso,
                'CodigoCuota' => $pagoProveedor['CodigoCuota'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Pago de cuota registrado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            //log error
            Log::error('Error al registrar pago de cuota', [
                'Controlador' => 'PagoProveedorController',
                'Metodo' => 'pagarCuota',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
