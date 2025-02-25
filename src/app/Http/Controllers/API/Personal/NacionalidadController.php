<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\Nacionalidad;
use Illuminate\Http\Request;

class NacionalidadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Nacionalidad::all();
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

    public function listarNacionalidad(){
        try{
            $nacionalidad = Nacionalidad::all();
            return response()->json($nacionalidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarNacionalidad(Request $request){
        try{
            Nacionalidad::create($request->all());
            return response()->json(['message' => 'Nacionalidad registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarNacionalidad($codigo){
        try{
            $nacionalidad = Nacionalidad::find($codigo);
            return response()->json($nacionalidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarNacionalidad(Request $request){
        try{
            $nacionalidad = Nacionalidad::find($request->Codigo);
            $nacionalidad->update($request->all());
            return response()->json(['message' => 'Nacionalidad actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}