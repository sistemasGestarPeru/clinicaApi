<?php

namespace App\Http\Controllers\API\PagoServicio;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\MotivoPagoServicio;
use App\Models\Recaudacion\PagoServicio;
use App\Models\Recaudacion\SalidaDinero;
use Illuminate\Http\Request;

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

    public function listarPagos(Request $request)
    {
        $fecha = $request->input('fecha');

        try {
            $results = DB::table('Egreso as e')
                ->join('PagoServicio as ps', 'ps.Codigo', '=', 'e.Codigo')
                ->join('MotivoPagoServicio as mps', 'mps.Codigo', '=', 'ps.CodigoMotivoPago')
                ->select('mps.Nombre', 'ps.Documento', DB::raw("DATE_FORMAT(e.Fecha, '%d/%m/%Y') as Fecha"))
                // ->where(DB::raw('DATE(e.Fecha)'), $fecha)
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
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $motivoPagoServicio = $request->input('motivoPagoServicio');
        $pagoServicio = $request->input('pagoServicio');
        $egreso = $request->input('egreso');
        $salidaDinero = $request->input('salidaDinero');

        DB::beginTransaction();
        try {

            $MotivoPagoData = MotivoPagoServicio::create($motivoPagoServicio);
            $idMotivoPago = $MotivoPagoData->Codigo;

            $egreso['Fecha'] = $fecha;
            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoServicio['Codigo'] = $idEgreso;
            $pagoServicio['CodigoMotivoPago'] = $idMotivoPago;
            PagoServicio::create($pagoServicio);

            $salidaDinero['Codigo'] = $idEgreso;
            SalidaDinero::create($salidaDinero);

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
