<?php

namespace App\Http\Controllers\API\TipoGravado;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\TipoGravado;
use Illuminate\Http\Request;

class TipoGravadoController extends Controller
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


    public function listarTipoGravado(){
        try{
            $entidad = TipoGravado::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarTipoGravado(Request $request){
        try{
            TipoGravado::create($request->all());
            return response()->json(['message' => 'Tigo de Gravado registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTipoGravado($codigo){
        try{
            $entidad = TipoGravado::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarTipoGravado(Request $request){
        try{
            $entidad = TipoGravado::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Tigo de Gravado actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
