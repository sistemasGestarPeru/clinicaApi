<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SedeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Sede::all();
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

    public function listarEmpresas(){
        try{
            
            $empresas = DB::table('empresas')
            ->select('Codigo', 'RazonSocial')
            ->where('Vigente', 1)
            ->get();
        
            return response()->json($empresas, 200);
            
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function listarSedes(){
        try{
            $sede = DB::table('sedesrec as s')
            ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
            ->join('departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
            ->select(
                's.Codigo',
                'd.Nombre as Departamento',
                'e.Nombre as Empresa',
                's.Nombre as Sede',
                's.Direccion',
                's.Telefono',
                's.Vigente'
            )
            ->get();
            return response()->json($sede, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarSede(Request $request){
        try{
            Sede::create($request->all());
            return response()->json(['message' => 'Sede registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarSede($codigo){
        try{
            $sede = Sede::find($codigo);
            return response()->json($sede, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarSede(Request $request){
        try{
            $sede = Sede::find($request->Codigo);
            $sede->update($request->all());
            return response()->json(['message' => 'Sede actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
