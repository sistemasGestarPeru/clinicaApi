<?php

namespace App\Http\Controllers\API\PagoDonante;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagoDonante;
use Illuminate\Support\Facades\DB;

class PagoDonanteController extends Controller
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




    public function registrarPagoDonante(Request $request)
    {
        $egreso = $request->input('egreso');
        $pagoDonante = $request->input('pagoDonante');
        DB::beginTransaction();
        try{
    
            //Validar Egreso
            $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();
            if ($egreso['CodigoCuentaOrigen'] == 0) {
                $egreso['CodigoCuentaOrigen'] = null;
            }
            $egreso = Egreso::create($egreso);

            $pagoDonante['Codigo'] = $egreso->Codigo;
            PagoDonante::create($pagoDonante);
            
            DB::commit();

            return response()->json([
                'message' => 'Pago del donante registrado correctamente'
            ], 201);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el pago del donante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarPagosDonante()
    {
        
    }

    public function consultarPagoDonante(string $id)
    {
        
    }

    public function actualizarPagoDonante(Request $request)
    {
        
    }
}
