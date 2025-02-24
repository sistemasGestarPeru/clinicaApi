<?php

namespace App\Http\Controllers\API\SistemaPensiones;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\SistemaPension;
use Illuminate\Http\Request;

class SistemaPensionesController extends Controller
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

    public function listarSistemaPensiones(){
        try{
            $sistemaPensiones = SistemaPension::all();
            return response()->json($sistemaPensiones, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarSistemaPensiones(Request $request){
        try{
            SistemaPension::create($request->all());
            return response()->json('Sistema de Pensión registrado correctamente.', 201);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarSistemaPensiones(Request $request){
        try{
            $sistemaPensiones = SistemaPension::find($request->Codigo);
            $sistemaPensiones->update($request->all());
            return response()->json('Sistema de Pensión actualizado correctamente.', 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarSistemaPensiones($codigo){
        try{
            $sistemaPensiones = SistemaPension::find($codigo);
            return response()->json($sistemaPensiones, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
