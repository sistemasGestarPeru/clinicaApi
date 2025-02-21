<?php

namespace App\Http\Controllers\API\MotivoNotaCredito;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoNotaCredito;
use Illuminate\Http\Request;

class MotivoNotaCreditoController extends Controller
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


    public function registrarMotivos(Request $request){

        $motivo = $request->input('motivo');
        try{
            MotivoNotaCredito::create($motivo);
            return response()->json(['message' => 'Motivo de Nota de Crédito registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarMotivos(){
        try{
            $motivos = MotivoNotaCredito::all();
            return response()->json($motivos, 200);
            
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
