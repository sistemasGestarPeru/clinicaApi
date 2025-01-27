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

    public function datosCajaExcel($caja){
        try {
            // Consulta para obtener datos de la caja
            $cajaData = DB::table('CAJA as c')
                ->join('Personas as p', 'p.Codigo', '=', 'c.CodigoTrabajador')
                ->select(
                    DB::raw("DATE_FORMAT(c.FechaInicio, '%d/%m/%Y') as FechaInicio"),
                    DB::raw("DATE_FORMAT(c.FechaFin, '%d/%m/%Y') as FechaFin"),
                    DB::raw("TIME(c.FechaInicio) as HoraInicio"),
                    DB::raw("TIME(c.FechaFin) as HoraFin"),
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Trabajador")
                )
                ->where('c.Codigo', $caja)
                ->first();
    
            // Consulta unida para obtener los movimientos de la caja
            $unionQuery = DB::select("
                SELECT 
                    Fecha,
                    OPERACION,
                    DOCUMENTO,
                    MONTO,
                    ROUND(@saldo := @saldo + MONTO, 2) AS SALDO
                FROM (
                    SELECT 
                        CONCAT(DATE_FORMAT(Fecha, '%d/%m/%Y'), ' ', TIME(Fecha)) AS FECHA,
                        CASE 
                            WHEN Tipo = 'A' THEN 'INGRESO APERTURA' 
                            ELSE 'INGRESO' 
                        END AS OPERACION,
                        '' AS DOCUMENTO,
                        Monto AS MONTO
                    FROM IngresoDinero 
                    WHERE CodigoCaja = 85 AND Vigente = 1
    
                    UNION ALL
    
                    SELECT 
                        CONCAT(DATE_FORMAT(Fecha, '%d/%m/%Y'), ' ', TIME(Fecha)) AS FECHA,
                        'RECAUDACION' AS OPERACION,
                        '' AS DOCUMENTO,
                        Monto AS MONTO
                    FROM Pago 
                    WHERE CodigoCaja = 85 
                        AND Vigente = 1 
                        AND CodigoMedioPago = (SELECT Codigo FROM mediopago WHERE Nombre LIKE '%Efectivo%')
    
                    UNION ALL
    
                    SELECT 
                        CONCAT(DATE_FORMAT(Fecha, '%d/%m/%Y'), ' ', TIME(Fecha)) AS FECHA,
                        'EGRESO' AS OPERACION,
                        '' AS DOCUMENTO,
                        -Monto AS MONTO
                    FROM Egreso 
                    WHERE CodigoCaja = 85 
                        AND Vigente = 1 
                        AND CodigoMedioPago = (SELECT Codigo FROM mediopago WHERE Nombre LIKE '%Efectivo%')
                ) AS result,
                (SELECT @saldo := 0) AS init
                ORDER BY FECHA;
            ");
    
            // Devolver los resultados
            return response()->json([
                'caja' => $cajaData,
                'movimientos' => $unionQuery
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al consultar la caja', 'error' => $e->getMessage()], 400);
        }
    }

    public function consultarDatosCaja($caja){
        try{

            $result = DB::table(DB::raw('(SELECT 1) as dummy')) // Usamos una tabla ficticia
            ->select([
                DB::raw("(SELECT DATE_FORMAT(FechaInicio, '%d/%m/%Y') 
                        FROM CAJA 
                        WHERE Codigo = $caja and Estado = 'A') AS Fecha"),
                DB::raw("(SELECT TIME(FechaInicio) 
                        FROM CAJA 
                        WHERE Codigo = $caja) AS Hora"),
                DB::raw("(SELECT SUM(Monto) 
                        FROM IngresoDinero 
                        WHERE CodigoCaja = $caja AND Vigente = 1) AS Ingresos"),
                DB::raw("(SELECT SUM(Monto) 
                        FROM Pago 
                        WHERE CodigoCaja = $caja AND Vigente = 1 AND 
                                CodigoMedioPago = (SELECT Codigo 
                                                FROM mediopago 
                                                WHERE Nombre LIKE '%Efectivo%')) AS Recaudacion"),
                DB::raw("(SELECT SUM(Monto) 
                        FROM Egreso 
                        WHERE CodigoCaja = $caja AND Vigente = 1 AND 
                                CodigoMedioPago = (SELECT Codigo 
                                                FROM mediopago 
                                                WHERE Nombre LIKE '%Efectivo%')) AS Egresos"),
            ])
            ->first();
    
            return response()->json($result, 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'Error al consultar la caja', 'error' => $e->getMessage()], 400);
        }
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

    public function estadoCajaLogin(Request $request)
    {
        $codigoTrabajador = $request->input('CodigoTrabajador');
        $codigoSede = $request->input('CodigoSede');

        try {
            $result = DB::table('caja')
                ->selectRaw("
                    CASE 
                        WHEN Estado = 'A' THEN Codigo
                        WHEN Estado = 'C' THEN -1
                    END AS CodigoCaja
                ")
                ->where('CodigoTrabajador', $codigoTrabajador)
                ->where('CodigoSede', $codigoSede)
                ->orderByDesc('Codigo')
                ->limit(1)
                ->first();

            // Si no hay resultado o el resultado es -1
            if (!$result || $result->CodigoCaja == -1) {
                return response()->json([
                    'CodigoCaja' => -1,
                    'resp' => false
                ], 200);
            }

            // Si el resultado es válido y positivo
            return response()->json([
                'CodigoCaja' => $result->CodigoCaja,
                'resp' => true
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al consultar la caja',
                'error' => $e->getMessage()
            ], 500); // Usar el código 500 para errores del servidor
        }
    }
    
}
