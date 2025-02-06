<?php

namespace App\Http\Controllers\API\PagoComision;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagoComision;
use Illuminate\Support\Facades\Validator;
class PagoComisionController extends Controller
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

    public function registrarPagoComision(Request $request)
    {
        $egreso = $request->input('egreso');
        $pagoComision = $request->input('pagoComision');
        DB::beginTransaction();
        try{

            //Validar Egreso
            $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();

            if ($egreso['CodigoCuentaOrigen'] == 0) {
                $egreso['CodigoCuentaOrigen'] = null;
            }

            $egreso = Egreso::create($egreso);

            $pagoComision['Codigo'] = $egreso->Codigo;
            PagoComision::create($pagoComision);
            DB::commit();

            return response()->json([
                'message' => 'Pago de comisión registrado correctamente'
            ], 201);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el pago de comisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarPagosComisiones(Request $request)
    {
        $data = $request->input('data');
        try{
            $resultados = DB::table('pagocomision as pc')
            ->select(
                'e.Codigo',
                DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Medico"),
                DB::raw('DATE(e.Fecha) as Fecha'),
                'e.Monto as Monto'
            )
            ->join('Egreso as e', 'e.Codigo', '=', 'pc.Codigo')
            ->join('Caja as c', 'c.Codigo', '=', 'e.CodigoCaja')
            ->join('Personas as p', 'p.Codigo', '=', 'pc.CodigoMedico')
            ->where('c.CodigoSede', $data['CodigoSede'])
            ->where('e.Vigente', 1)
            ->get();

            return response()->json($resultados, 200);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al listar los pagos de comisiones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarPagoComision(string $id)
    {
        
    }

    public function actualizarPagoComision(Request $request, string $id)
    {
        
    }
}
