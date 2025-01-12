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
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $cajaData = $request->input('Caja');
        $IngresoDineroData = $request->input('IngresoDinero');

        $cajaData['FechaInicio'] = $fecha;
        $cajaData['Estado'] = 'A';
        
        DB::beginTransaction();

        try {

            $caja = Caja::create($cajaData);
            $codigo = $caja->Codigo;

            $IngresoDineroData['CodigoCaja'] = $codigo;
            $IngresoDineroData['Fecha'] = $fecha;
            $IngresoDineroData['Tipo'] = 'A';
            IngresoDinero::create($IngresoDineroData);
            DB::commit();
            return response()->json([
                'CodigoCaja' => $codigo,
                'resp' => true
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al registrar la caja',
                'resp' => false
            ], 400);
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
                'CodigoCaja' => -1,
                'resp' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cerrar la caja'
            ], 400);
        }
    }

    public function registrarIngreso(Request $request)
    {
        try {
            $IngresoDineroData = $request->input('IngresoDinero');

            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d H:i:s');

            $IngresoDineroData['Fecha'] = $fecha;
            $IngresoDineroData['Tipo'] = 'I';

            IngresoDinero::create($IngresoDineroData);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al registrar el ingreso', 'error' => $e->getMessage()], 400);
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
                ->where('Estado', 'A')
                ->orderByDesc('Codigo')
                ->select('Estado', 'Codigo')
                ->first();

            if ($caja) {
                $result = DB::table('ingresodinero as i')
                    ->select(
                        'c.FechaInicio',
                        DB::raw('SUM(CASE WHEN i.Tipo = "I" THEN i.Monto ELSE 0 END) AS TotalMonto'),
                        DB::raw('SUM(CASE WHEN i.Tipo = "A" THEN i.Monto ELSE 0 END) AS MontoApertura')
                    )
                    ->join('caja as c', 'c.Codigo', '=', 'i.CodigoCaja')
                    ->where('i.CodigoCaja', $caja->Codigo)
                    ->where('c.Estado', 'A')
                    ->groupBy('i.CodigoCaja')
                    ->first();
                return response()->json(['caja' => $caja, 'result' => $result], 200);
            } else {
                return response()->json(['message' => 'No se encontró caja abierta'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al consultar la caja', 'error' => $e->getMessage()], 400);
        }
    }

    public function consultarEstadoCaja(Request $request)
    {
        $codigoTrabajador = $request->input('CodigoTrabajador');
        $codigoSede = $request->input('CodigoSede');
        try {
            $caja = DB::table('clinica_db.caja')
                ->where('CodigoTrabajador', $codigoTrabajador)
                ->where('CodigoSede', $codigoSede)
                ->where('Estado', 'A')
                ->orderByDesc('Codigo')
                ->select('Codigo')
                ->first();
            return response()->json(['caja' => $caja], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al consultar la caja', 'error' => $e->getMessage()], 400);
        }
    }
}
