<?php

namespace App\Http\Controllers\API\UnidadMedida;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\UnidadMedida;
use Illuminate\Http\Request;

class UnidadMedidaController extends Controller
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

    public function listarUnidadMedidad(){
        try{
            $entidad = UnidadMedida::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarUnidadMedidad(Request $request){
        try{
            UnidadMedida::create($request->all());
            return response()->json(['message' => 'Unidad de Medida registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarUnidadMedidad($codigo){
        try{
            $entidad = UnidadMedida::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarUnidadMedidad(Request $request){
        try{
            $entidad = UnidadMedida::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Unidad de Medida actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
