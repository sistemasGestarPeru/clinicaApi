<?php

namespace App\Http\Controllers\API\MotivoAnulacionContrato;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoAnulacionContrato;
use Illuminate\Http\Request;

class MotivoAnulacionContratoController extends Controller
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

    public function listarMotivoAnulacionContrato(){
        try{
            $motivoAnulacionContrato = MotivoAnulacionContrato::all();
            return response()->json($motivoAnulacionContrato, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMotivoAnulacionContrato(Request $request){
        try{
            MotivoAnulacionContrato::create($request->all());
            return response()->json(['message' => 'Motivo de anulación de contrato registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivoAnulacionContrato($codigo){
        try{
            $motivoAnulacionContrato = MotivoAnulacionContrato::find($codigo);
            return response()->json($motivoAnulacionContrato, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivoAnulacionContrato(Request $request){
        try{
            $motivoAnulacionContrato = MotivoAnulacionContrato::find($request->Codigo);
            $motivoAnulacionContrato->update($request->all());
            return response()->json(['message' => 'Motivo de anulación de contrato actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
