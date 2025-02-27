<?php

namespace App\Http\Controllers\API\Moneda;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Moneda;
use Illuminate\Http\Request;

class MonedaController extends Controller
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

    public function listarMoneda(){
        try{
            $entidad = Moneda::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMoneda(Request $request){
        try{
            Moneda::create($request->all());
            return response()->json(['message' => 'Unidad de Medida registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMoneda($codigo){
        try{
            $entidad = Moneda::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMoneda(Request $request){
        try{
            $entidad = Moneda::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Unidad de Medida actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
