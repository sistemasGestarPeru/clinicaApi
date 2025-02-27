<?php

namespace App\Http\Controllers\API\EntidadBancaria;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\CuentaBancaria;
use App\Models\Recaudacion\EntidadBancaria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntidadBancariaController extends Controller
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

    public function listarEntidadBancaria(){
        try{
            $entidad = EntidadBancaria::all();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarEntidadBancaria(Request $request){
        try{
            EntidadBancaria::create($request->all());
            return response()->json(['message' => 'Entidad Bancaria registrada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarEntidadBancaria($codigo){
        try{
            $entidad = EntidadBancaria::find($codigo);
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEntidadBancaria(Request $request){
        try{
            $entidad = EntidadBancaria::find($request->Codigo);
            $entidad->update($request->all());
            return response()->json(['message' => 'Entidad Bancaria actualizada correctamente'], 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    // LOCAL EMPRESA CUENTA BANCARIA

    public function cuentaBancariaEmpresa($empresa){
        try{
            $cuentasBancarias = DB::table('cuentabancaria as cb')
            ->join('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria')
            ->join('tipoMoneda as tm', 'tm.Codigo', '=', 'cb.CodigoTipoMoneda')
            ->join('empresas as e', 'e.Codigo', '=', 'cb.CodigoEmpresa')
            ->select(
                'cb.Codigo',
                'cb.Numero',
                'cb.CCI',
                'tm.Nombre as NombreMoneda',
                'eb.Nombre as NombreBanco',
                'e.Nombre as NombreEmpresa',
                'cb.Detraccion'
            )
            ->where('e.Codigo', $empresa)
            ->get();

            return response()->json($cuentasBancarias, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarCuentaBancaria($codigo){
        try{
            $cuentaBancaria = CuentaBancaria::find($codigo);
            return response()->json($cuentaBancaria, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarCuentaBancaria(Request $request) {
        try {
            // Validar que Detraccion sea único si es 1 y esté activo
            if ($request->Detraccion == 1) {
                $existeDetraccion = DB::table('cuentabancaria')
                    ->where('Detraccion', 1)
                    ->where('Vigente', 1) // Solo cuentas activas
                    ->exists();
    
                if ($existeDetraccion) {
                    return response()->json(['error' => 'Ya existe una cuenta activa con Detracción'], 400);
                }
            }
    
            // Crear la cuenta bancaria
            CuentaBancaria::create($request->all());
    
            return response()->json(['message' => 'Cuenta Bancaria registrada correctamente'], 200);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function actualizarCuentaBancaria(Request $request) {
        try {
            $cuentaBancaria = CuentaBancaria::find($request->Codigo);
            
            if (!$cuentaBancaria) {
                return response()->json(['error' => 'Cuenta Bancaria no encontrada'], 404);
            }
    
            // Si la cuenta bancaria que se está actualizando tiene Detraccion = 1
            if ($request->Detraccion == 1 && $request->Vigente == 1) {
                $existeOtraDetraccionActiva = DB::table('cuentabancaria')
                    ->where('Detraccion', 1)
                    ->where('Vigente', 1)
                    ->where('Codigo', '!=', $request->Codigo) // Excluir la cuenta que se está actualizando
                    ->exists();
    
                if ($existeOtraDetraccionActiva) {
                    return response()->json(['error' => 'Ya existe otra cuenta activa con Detracción'], 400);
                }
            }
    
            // Actualizar la cuenta bancaria
            $cuentaBancaria->update($request->all());
    
            return response()->json(['message' => 'Cuenta Bancaria actualizada correctamente'], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
}
