<?php

namespace App\Http\Controllers\API\Proveedor;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
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

    public function listarProveedor(){
        try{
            $entidad = Proveedor::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar Proveedores','bd' => $e->getMessage()], 500);
        }
    }

    public function consultarProveedor($codigo){
        try{
            $entidad = Proveedor::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al consultar Proveedores','bd' => $e->getMessage()], 500);
        }
    }

    public function registrarProveedor(Request $request){
        try{
            Proveedor::create($request->all());
            return response()->json(['message' => 'Proveedor registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al registrar Proveedor','bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarProveedor(Request $request){
        try{
            $entidad = Proveedor::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Proveedor actualizado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al actualizar Proveedor','bd' => $e->getMessage()], 500);
        }
    }
}
