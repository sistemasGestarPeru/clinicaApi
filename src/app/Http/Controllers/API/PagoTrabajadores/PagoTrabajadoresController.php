<?php

namespace App\Http\Controllers\API\PagoTrabajadores;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagoTrabajadores\PagoPlanilla;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagoTrabajadoresController extends Controller
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

    public function registrarPlanilla(Request $request)
    {
        $planillas = $request->input('planilla');
        $egreso = $request->input('egreso');

        foreach($egreso as $index => $egresos){
            $egresoValidator = Validator::make($egresos, [
                'Fecha' => 'required|date',
                'Monto' => 'required|integer',
                'CodigoCaja' => 'required|integer',
                'CodigoTrabajador' => 'required|integer',
                'CodigoMedioPago' => 'required|integer',
            ]);

            if ($egresoValidator->fails()) {
                return response()->json([
                    'error' => $egresoValidator->errors(),
                    'mensaje' => "Error en el trabajador del índice $index "
                ], 400);
            }

        }

        // Validar cada objeto dentro del arreglo
        foreach ($planillas as $index => $planilla) {
            $planillaValidator = Validator::make($planilla, [
                'CodigoSistemaPensiones' => 'required|integer',
                'CodigoTrabajador' => 'required|integer',
                'Mes' => 'required|integer',
                //'MontoPension' => 'required|integer',
                // 'MontoSeguro' => 'required|integer',
                // 'ReciboHonorario' => 'nullable|integer',
            ]);
    
            if ($planillaValidator->fails()) {
                return response()->json([
                    'error' => $planillaValidator->errors(),
                    'mensaje' => "Error en la planilla del índice ". ($index + 1)
                ], 400);
            }
        }
    
        DB::beginTransaction();
        try {

            foreach ($egreso as $index => $egresos) {
                
                $nuevoEgresoId = DB::table('egreso')->insertGetId($egresos);
            
                $planilla = $planillas[$index];
                $planilla['Codigo'] = $nuevoEgresoId;
            
                PagoPlanilla::create($planilla);
            }
            DB::commit();
            return response()->json(['mensaje' => 'Planilla registrada correctamente'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al registrar la planilla'], 500);
        }
    }


    public function listarTrabajadoresPlanilla(Request $request)
    {
        $empresa = $request->input('empresa');

        try {
            $resultado = DB::table('personas as p')
                ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('SistemaPensiones as sp', 'sp.Codigo', '=', 't.CodigoSistemaPensiones')
                ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->select(
                    't.Codigo as CodigoTrabajador',
                    'p.Nombres',
                    'p.Apellidos',
                    'sp.Nombre as Pension',
                    'sp.Codigo as CodigoSistemaPensiones',
                    'cl.SueldoBase'
                )
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('cl.Vigente', 1)
                ->where('cl.CodigoEmpresa', $empresa)
                ->orderBy('p.Codigo')
                ->get();

            return response()->json($resultado, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al listar trabajadores'], 500);
        }
    }

    public function buscarTrabajadorPago(Request $request){

        $empresa = $request->input('empresa');
        $tipoDocumento = $request->input('tipoDocumento');
        $numeroDocumento = $request->input('numeroDocumento');

        try{

            $results = DB::table('trabajadors as t')
                ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
                ->join('SistemaPensiones as sp', 'sp.Codigo', '=', 't.CodigoSistemaPensiones')
                ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.NumeroDocumento',
                    'cl.SueldoBase',
                    'sp.Nombre as Pension',
                    'sp.Codigo as CodigoSistemaPensiones'
                )
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('cl.Vigente', 1)
                ->where('cl.CodigoEmpresa', $empresa)
                ->where('p.CodigoTipoDocumento', $tipoDocumento)
                ->where('p.NumeroDocumento', $numeroDocumento)
                ->first();

                return response()->json($results, 200); 

        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al buscar el trabajador'], 500);
        }
    }
}
