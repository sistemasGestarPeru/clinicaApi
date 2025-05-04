<?php

namespace App\Http\Controllers\API\Recaudacion\FacturacionElectronica;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\FacturacionElectronica\EnvioFacturacion;
use Illuminate\Http\Request;

class FacturacionElectronicaController extends Controller
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

    public function registrarEnvio(Request $request){
        $fechaActual = date('Y-m-d');
        $data = $request->all();
        $data['Fecha'] = $fechaActual;
        try{
            EnvioFacturacion::create($data);
            return response()->json([
                'message' => 'Envio de la factura electronica registrado correctamente',
                'data' => $data
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al registrar el envio de la factura electronica',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
