<?php

namespace App\Http\Controllers\API\EntidadBancaria;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\EntidadBancaria;
use Illuminate\Http\Request;

class EntidadBancariaController extends Controller
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

    public function listarEntidadBancaria(){
        try{
            $entidad = EntidadBancaria::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarEntidadBancaria(Request $request){
        try{
            EntidadBancaria::create($request->all());
            return response()->json(['message' => 'Entidad Bancaria registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarEntidadBancaria($codigo){
        try{
            $entidad = EntidadBancaria::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEntidadBancaria(Request $request){
        try{
            $entidad = EntidadBancaria::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Entidad Bancaria actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
