<?php

namespace App\Http\Controllers\API\BilleteraDigital;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\BilleteraDigital;
use App\Models\Recaudacion\LocalBilleteraDigital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            return response()->json(['message' => 'Entidad Billetera Digital registrada correctamente'], 200);
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
            return response()->json(['message' => 'Entidad Billetera Digital actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    //Billetera Digital - Empresa

    public function listarBilleteraDigital($codigo){
        try{
            $datos = DB::table('billeteradigital as bd')
            ->join('entidadbilleteradigital as ebd', 'ebd.Codigo', '=', 'bd.CodigoEntidadBilleteraDigital')
            ->join('empresas as e', 'e.Codigo', '=', 'bd.CodigoEmpresa')
            ->select(
                'bd.Codigo',
                'ebd.Nombre as Billetera',
                'e.Nombre as Empresa',
                'bd.Numero',
                'bd.Vigente'
            )
            ->where('e.Codigo', $codigo)
            ->get();
            return response()->json($datos, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarBilleteraDigital(Request $request){
        try{
            LocalBilleteraDigital::create($request->all());
            return response()->json(['message' => 'Billetera Digital registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarBilleteraDigital($codigo){
        try{
            $entidad = LocalBilleteraDigital::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarBilleteraDigital(Request $request){
        try{
            $entidad = LocalBilleteraDigital::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Billetera Digital actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
