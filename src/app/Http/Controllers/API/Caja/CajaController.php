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

        $cajaData['FechaInicio'] = $fecha;
        $cajaData['Estado'] = 'A';
        
        DB::beginTransaction();

        try {
            $cajaConsulta = DB::table('Caja')
                ->select('TotalEfectivo')
                ->where('CodigoTrabajador', $cajaData['CodigoTrabajador'])
                ->where('Estado', 'C')
                ->where('CodigoSede', $cajaData['CodigoSede'])
                ->orderByDesc('Codigo')
                ->limit(1)
            ->first();
        
            $caja = Caja::create($cajaData);
            $codigo = $caja->Codigo;
            $IngresoDineroData['Fecha'] = $fecha;
            $IngresoDineroData['Monto'] = $cajaConsulta->TotalEfectivo;
            $IngresoDineroData['Tipo'] = 'A';
            $IngresoDineroData['CodigoCaja'] = $codigo;
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
                    WHERE CodigoCaja = $caja AND Vigente = 1
    
                    UNION ALL
    
                    SELECT 
                        CONCAT(DATE_FORMAT(Fecha, '%d/%m/%Y'), ' ', TIME(Fecha)) AS FECHA,
                        'RECAUDACION' AS OPERACION,
                        '' AS DOCUMENTO,
                        Monto AS MONTO
                    FROM Pago 
                    WHERE CodigoCaja = $caja 
                        AND Vigente = 1 
                        AND CodigoMedioPago = (SELECT Codigo FROM mediopago WHERE Nombre LIKE '%Efectivo%')
    
                    UNION ALL
    
                    SELECT 
                        CONCAT(DATE_FORMAT(Fecha, '%d/%m/%Y'), ' ', TIME(Fecha)) AS FECHA,
                        'EGRESO' AS OPERACION,
                        '' AS DOCUMENTO,
                        -Monto AS MONTO
                    FROM Egreso 
                    WHERE CodigoCaja = $caja 
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

            $result = DB::table('CAJA')
            ->leftJoin('IngresoDinero as apertura', function ($join) use ($caja) {
                $join->on('apertura.CodigoCaja', '=', 'CAJA.Codigo')
                     ->where('apertura.Vigente', 1)
                     ->where('apertura.Tipo', 'A');
            })
            ->leftJoin('IngresoDinero as ingresos', function ($join) use ($caja) {
                $join->on('ingresos.CodigoCaja', '=', 'CAJA.Codigo')
                     ->where('ingresos.Vigente', 1);
            })
            ->leftJoin('Pago as pagos', function ($join) {
                $join->on('pagos.CodigoCaja', '=', 'CAJA.Codigo')
                     ->where('pagos.Vigente', 1)
                     ->whereIn('pagos.CodigoMedioPago', function ($query) {
                         $query->select('Codigo')
                               ->from('mediopago')
                               ->where('Nombre', 'LIKE', '%Efectivo%');
                     });
            })
            ->leftJoin('Egreso as egresos', function ($join) {
                $join->on('egresos.CodigoCaja', '=', 'CAJA.Codigo')
                     ->where('egresos.Vigente', 1)
                     ->whereIn('egresos.CodigoMedioPago', function ($query) {
                         $query->select('Codigo')
                               ->from('mediopago')
                               ->where('Nombre', 'LIKE', '%Efectivo%');
                     });
            })
            ->where('CAJA.Codigo', $caja)
            ->where('CAJA.Estado', 'A')
            ->select([
                DB::raw("COALESCE((SELECT Monto FROM IngresoDinero WHERE CodigoCaja = CAJA.Codigo AND Vigente = 1 AND Tipo = 'A' LIMIT 1), 0) AS Apertura"),
                DB::raw("DATE_FORMAT(FechaInicio, '%d/%m/%Y') AS Fecha"),
                DB::raw("TIME(FechaInicio) AS Hora"),
                DB::raw("COALESCE(SUM(ingresos.Monto), 0) AS Ingresos"),
                DB::raw("COALESCE(SUM(pagos.Monto), 0) AS Recaudacion"),
                DB::raw("COALESCE(SUM(egresos.Monto), 0) AS Egresos"),
            ])
            ->groupBy('CAJA.Codigo')
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
            $request->merge(['TotalEfectivo' => $request->Total]);
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
        $IngresoDineroData = $request->input('IngresoDinero');
        $egreso = $request->input('Egreso');
        DB::beginTransaction();
        try {
            $IngresoDineroData = $request->input('IngresoDinero');

            $IngresoDineroData['Tipo'] = 'I';

            IngresoDinero::create($IngresoDineroData);
            
            DB::table('salidadinero')
            ->where('Codigo', $egreso)
            ->update([
                'Confirmado' => 1
            ]);
            DB::commit();
            return response()->json(['message' => 'Ingreso registrado correctamente', $egreso], 201);
        } catch (\Exception $e) {
            DB::rollBack();
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



    public function listarIngresosPendientes(Request $request){
        $codigoSede = $request->input('codigoSede');
        $codigoReceptor = $request->input('codigoReceptor');

        try{
            $results = DB::table('clinica_db.salidadinero AS sd')
                ->join('Egreso AS e', 'e.Codigo', '=', 'sd.Codigo')
                ->join('Caja AS c', 'c.Codigo', '=', 'e.CodigoCaja')
                ->join('Personas AS p', 'p.Codigo', '=', 'e.CodigoTrabajador')
                ->where('sd.Confirmado', 0)
                ->where('e.Vigente', 1)
                ->where('c.CodigoSede', $codigoSede)
                ->where('sd.CodigoReceptor', $codigoReceptor)
                ->whereNull('sd.CodigoCuentaBancaria')
                ->select(
                    'e.Codigo AS CodigoEgreso',
                    'p.Codigo AS CodigoEmisor',
                    'e.Monto',
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) AS Emisor")
                )
            ->get();

            return response()->json($results, 200);

        }catch(\Exception $e){
            return response()->json(['message' => 'Error al listar los ingresos pendientes', 'error' => $e->getMessage()], 400);
        }
        
    }
    
}
