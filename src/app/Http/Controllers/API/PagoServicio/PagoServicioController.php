<?php

namespace App\Http\Controllers\API\PagoServicio;

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
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;

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
        try {
            $pagoServicio = PagoServicio::find($codigo);
            $egreso = Egreso::find($codigo);

            if ($pagoServicio) {
                return response()->json([
                    'pagoServicio' => $pagoServicio,
                    'egreso' => $egreso
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Pago del servicio no encontrado'
                ], 404);
            }
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
            $results = DB::table('egreso as e')
                ->join('pagoservicio as ps', 'ps.Codigo', '=', 'e.Codigo')
                ->join('motivopagoservicio as mps', 'mps.Codigo', '=', 'ps.CodigoMotivoPago')
                ->join('caja as c', 'c.Codigo', '=', 'e.CodigoCaja')
                ->select('ps.Codigo', 'mps.Nombre', 'ps.TipoDocumento', DB::raw("DATE_FORMAT(e.Fecha, '%d/%m/%Y') as Fecha"))
                // ->where(DB::raw('DATE(e.Fecha)'), $fecha)
                ->where('c.CodigoSede', $sede)
                ->orderBy('e.Fecha', 'desc')
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
        $pagoServicio = $request->input('pagoServicio');
        $egreso = $request->input('egreso');


        //Validar PagoServicio
        $pagoServicioValidator = Validator::make($pagoServicio, (new RegistrarPagoServicioRequest())->rules());
        $pagoServicioValidator->validate();

        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();


        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon
        $fechaPagoVal = Carbon::parse($pagoServicio['FechaDocumento'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura de caja.'], 400);
        }
        if ($fechaCajaVal < $fechaPagoVal) {
            return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
        }

        if(isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0){
            $egreso['CodigoCuentaOrigen'] = null;
        }

        if(isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0){
            $egreso['CodigoBilleteraDigital'] = null;
        }

        if ($egreso['CodigoSUNAT'] == '008') {
            $egreso['CodigoCuentaOrigen'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
            $egreso['NumeroOperacion'] = null;

            $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

            if($egreso['Monto'] > $total){
                return response()->json(['error' => 'No hay suficiente Efectivo en caja', 'Disponible' => $total ], 500);
            }

        }else if($egreso['CodigoSUNAT'] == '003'){
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;

        }else if($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006'){
            $egreso['CodigoCuentaBancaria'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
        }


        DB::beginTransaction();
        try {

            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoServicio['Codigo'] = $idEgreso;
            PagoServicio::create($pagoServicio);

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
}
