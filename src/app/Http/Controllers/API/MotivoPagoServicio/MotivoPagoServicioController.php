<?php

namespace App\Http\Controllers\API\MotivoPagoServicio;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoPagoServicio;
use Illuminate\Http\Request;

class MotivoPagoServicioController extends Controller
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


    public function listarMotivoPagoServicios(){
        try{
            $motivoPagoServicio = MotivoPagoServicio::all();
            return response()->json($motivoPagoServicio, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMotivoPagoServicios(Request $request){
        try{
            MotivoPagoServicio::create($request->all());
            return response()->json(['message' => 'Motivo Pago de Servicio registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivoPagoServicios($codigo){
        try{
            $motivoPagoServicio = MotivoPagoServicio::find($codigo);
            return response()->json($motivoPagoServicio, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivoPagoServicios(Request $request){
        try{
            $motivoPagoServicio = MotivoPagoServicio::find($request->Codigo);
            $motivoPagoServicio->update($request->all());
            return response()->json(['message' => 'Motivo Pago de Servicio actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
