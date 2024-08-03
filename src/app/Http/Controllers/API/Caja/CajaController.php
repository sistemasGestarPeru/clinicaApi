<?php

namespace App\Http\Controllers\API\Caja;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Caja\RegistrarCajaRequest;
use App\Http\Requests\Recaudacion\IngresoDinero\RegistrarIngresoDineroRequest;
use App\Http\Resources\Recaudacion\Caja\CajaResource;
use App\Models\Recaudacion\Caja;
use App\Models\Recaudacion\IngresoDinero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CajaController extends Controller
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

        $cajaData = $request->input('Caja');
        $IngresoDineroData = $request->input('IngresoDinero');

        DB::beginTransaction();
        try {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d H:i:s');
            $cajaData['FechaInicio'] = $fecha;

            $caja = Caja::create($cajaData);

            $codigo = $caja->Codigo;

            $IngresoDineroData['CodigoCaja'] = $codigo;
            $IngresoDineroData['Fecha'] = $fecha;
            $IngresoDineroData['Tipo'] = 'A';
            IngresoDinero::create($IngresoDineroData);
            DB::commit();
            return response()->json([
                'message' => 'Caja registrada correctamente',
                'codigo' => $codigo
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la caja: ', $e], 400);
        }
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

    public function cerrarCaja(Request $request)
    {
        try {

            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d H:i:s');
            $request->merge(['FechaFin' => $fecha]);
            $request->merge(['Estado' => 'C']);

            $caja = Caja::where('Codigo', $request->CodigoCaja)->first();
            $caja->update($request->all());

            return response()->json([
                'message' => 'Caja cerrada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cerrar la caja'], 400);
        }
    }

    public function consultarCaja(Request $request)
    {
        try {
            $codigoTrabajador = $request->input('CodigoTrabajador');
            $codigoSede = $request->input('CodigoSede');

            $caja = DB::table('clinica_db.caja')
                ->where('CodigoTrabajador', $codigoTrabajador)
                ->where('CodigoSede', $codigoSede)
                ->orderByDesc('Codigo')
                ->select('Estado', 'Codigo')
                ->first(); // Get the first record

            return response()->json(['resp' => $caja], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al consultar la caja', 'error' => $e->getMessage()], 400);
        }
    }
}
