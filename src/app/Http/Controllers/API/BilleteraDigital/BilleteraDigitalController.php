<?php

namespace App\Http\Controllers\API\BilleteraDigital;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\BilleteraDigital;
use Illuminate\Http\Request;

class BilleteraDigitalController extends Controller
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

    public function listarEntidadBilleteraDigital(){
        try{
            $entidad = BilleteraDigital::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarEntidadBilleteraDigital(Request $request){
        try{
            BilleteraDigital::create($request->all());
            return response()->json(['message' => 'Billetera Digital registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarEntidadBilleteraDigital($codigo){
        try{
            $entidad = BilleteraDigital::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEntidadBilleteraDigital(Request $request){
        try{
            $entidad = BilleteraDigital::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Billetera Digital actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
