<?php

namespace App\Http\Controllers\API\MedioPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recaudacion\MedioPago;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MedioPagoController extends Controller
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

    public function listarMedioPago(){
        try{
            $entidad = MedioPago::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMedioPago(Request $request)
    {
        if (MedioPago::where('CodigoSUNAT', $request->CodigoSUNAT)->exists()) {
            return response()->json([
                'error' => 'El Código SUNAT ya existe.'
            ], 500);
        }

        try {
            // Intentar registrar el nuevo medio de pago
            MedioPago::create($request->all());
    
            return response()->json(['message' => 'Medio de Pago registrado correctamente'], 201);
    
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'error' => 'El medio de pago ya existe. Intente con otro nombre.'
                ], 500);
            }
    
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Error en la base de datos. Inténtelo nuevamente.'
            ], 500);
            
        } catch (\Exception $e) {
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function consultarMedioPago($codigo){
        try{
            $entidad = MedioPago::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMedioPago(Request $request){
        try{
            $entidad = MedioPago::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Medio de Pago actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Local Medio Pago

    public function mediosPagoDisponible($sede){
        try{
            $mediosPago = DB::table('medioPago')
                ->whereNotIn('codigo', function ($query) use ($sede) {
                    $query->select('CodigoMedioPago')
                        ->from('localmediopago')
                        ->where('CodigoSede', $sede);
                })
                ->get();
                return response()->json($mediosPago, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarLocalMedioPago($sede){
        try{
            $mediosPago = DB::table('localmediopago as lmp')
            ->join('mediopago as mp', 'mp.Codigo', '=', 'lmp.CodigoMedioPago')
            ->join('sedesrec as s', 's.Codigo', '=', 'lmp.CodigoSede')
            ->where('lmp.CodigoSede', $sede)
            ->select('lmp.Codigo', 'mp.Nombre', 'lmp.Vigente', 's.Nombre as Sede')
            ->get();
            return response()->json($mediosPago, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarLocalMedioPago(Request $request){
        try{
            DB::table('localmediopago')->insert([
                'CodigoSede' => $request->CodigoSede,
                'CodigoMedioPago' => $request->CodigoMedioPago
            ]);
            return response()->json(['message' => 'Medio de Pago registrado correctamente'], 201);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarLocalMedioPago(Request $request){
        try{
            DB::table('localmediopago')
                ->where('Codigo', $request->Codigo)
                ->update([
                    'CodigoMedioPago' => $request->CodigoMedioPago,
                    'Vigente' => $request->Vigente
                ]);
            return response()->json(['message' => 'Medio de Pago actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMedioPagoLocal($codigo){
        try{
            $entidad = DB::table('localmediopago')
                ->where('Codigo', $codigo)
                ->select('Codigo', 'CodigoMedioPago' ,'Vigente')
                ->first();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
