<?php

namespace App\Http\Controllers\API\MedioPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recaudacion\MedioPago;

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

    public function registrarMedioPago(Request $request){
        try{
            MedioPago::create($request->all());
            return response()->json(['message' => 'Medio de Pago registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
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
}
