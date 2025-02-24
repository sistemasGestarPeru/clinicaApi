<?php

namespace App\Http\Controllers\API\MotivoAnulacionVenta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoAnulacionVenta;
use Illuminate\Http\Request;

class MotivoAnulacionVentaController extends Controller
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

    public function listarMotivoAnulacionVenta(){
        try{
            $motivoAnulacion = MotivoAnulacionVenta::all();
            return response()->json($motivoAnulacion, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMotivoAnulacionVenta(Request $request){
        try{
            MotivoAnulacionVenta::create($request->all());
            return response()->json(['message' => 'Motivo de Anulación de Venta registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivoAnulacionVenta($codigo){
        try{
            $motivoAnulacion = MotivoAnulacionVenta::find($codigo);
            return response()->json($motivoAnulacion, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivoAnulacionVenta(Request $request){
        try{
            $motivoAnulacion = MotivoAnulacionVenta::find($request->Codigo);
            $motivoAnulacion->update($request->all());
            return response()->json(['message' => 'Motivo de Anulación de Venta actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
