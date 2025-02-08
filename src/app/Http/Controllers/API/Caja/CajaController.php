<?php

namespace App\Http\Controllers\API\Caja;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Caja\RegistrarCajaRequest;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Http\Requests\Recaudacion\IngresoDinero\RegistrarIngresoDineroRequest;
use App\Http\Resources\Recaudacion\Caja\CajaResource;
use App\Models\Recaudacion\Caja;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\IngresoDinero;
use App\Models\Recaudacion\SalidaDinero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
                    FECHA,
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
                        CONCAT(DATE_FORMAT(p.Fecha, '%d/%m/%Y'), ' ', TIME(p.Fecha)) AS FECHA,
                        'RECAUDACION' AS OPERACION,
                        CONCAT(dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) AS DOCUMENTO,
                        p.Monto AS MONTO
                    FROM Pago as p
                    INNER JOIN pagodocumentoventa as pdv ON pdv.CodigoPago = p.Codigo
                    INNER JOIN documentoventa as dv ON dv.Codigo = pdv.CodigoDocumentoVenta
                    WHERE p.CodigoCaja = $caja 
                        AND dv.CodigoMotivoNotaCredito IS NULL
                        AND p.Vigente = 1 
                        AND p.CodigoMedioPago = (SELECT Codigo FROM mediopago WHERE CodigoSUNAT = '008')
    
                    UNION ALL
    
                SELECT 
                    CONCAT(DATE_FORMAT(Fecha, '%d/%m/%Y'), ' ', TIME(Fecha)) AS FECHA,
                    CASE
                            WHEN ps.Codigo IS NOT NULL THEN 'PAGO DE SERVICIOS'
                        WHEN pp.Codigo IS NOT NULL THEN 'PAGO A PROVEEDOR'
                        WHEN sd.Codigo IS NOT NULL THEN 'SALIDA DE DINERO'
                        WHEN dnc.Codigo IS NOT NULL THEN 'DEVOLUCIÓN NOTA CRÉDITO'
                        WHEN pd.Codigo IS NOT NULL THEN 'PAGO DONANTE'
                        WHEN pc.Codigo IS NOT NULL THEN 'PAGO COMISIÓN'
                        WHEN pper.Codigo IS NOT NULL THEN 'PAGO PERSONAL'
                    END AS OPERACION,
                    
                    CASE 
                        WHEN ps.Codigo IS NOT NULL THEN CONCAT(ps.TipoDocumento,' ',ps.Serie,'-',ps.Numero)
                        WHEN pp.CodigoCuota IS NOT NULL THEN (
                                SELECT CONCAT(tdv.Nombre,' ',Co.Serie, '-', LPAD(Co.Numero, 5, '0')) FROM Cuota as c 
                            INNER JOIN Compra as Co ON c.CodigoCompra = Co.Codigo
                            INNER JOIN tipodocumentoventa as tdv ON tdv.Codigo = Co.CodigoTipoDocumentoVenta
                            WHERE c.Codigo = pp.CodigoCuota AND c.Vigente = 1 AND Co.Vigente = 1)
                            WHEN dnc.Codigo IS NOT NULL THEN (
                                SELECT CONCAT(tdv.Nombre,' ',docv.Serie, '-', LPAD(docv.Numero, 5, '0')) 
                            FROM DocumentoVenta as docv 
                            INNER JOIN tipodocumentoventa as tdv ON tdv.Codigo = docv.CodigoTipoDocumentoVenta
                            WHERE docv.Vigente = 1 AND docv.Codigo = dnc.CodigoDocumentoVenta)
                        ELSE '-' 
                        END
                        AS DOCUMENTO,
                    
                    -e.Monto AS MONTO
                        FROM Egreso as e
                        LEFT JOIN PagoServicio AS ps ON ps.Codigo = e.Codigo
                        LEFT JOIN PagoProveedor as pp ON pp.Codigo = e.Codigo
                        LEFT JOIN SalidaDinero AS sd ON sd.Codigo = e.Codigo
                        LEFT JOIN devolucionnotacredito as dnc ON dnc.Codigo = e.Codigo
                        LEFT JOIN pagoDonante as pd ON pd.Codigo = e.Codigo
                        LEFT JOIN pagoComision as pc ON pc.Codigo = e.Codigo
                        LEFT JOIN pagopersonal as pper ON pper.Codigo = e.Codigo
                        WHERE CodigoCaja = $caja 
                    AND Vigente = 1 
                    AND CodigoMedioPago = (SELECT Codigo FROM mediopago WHERE CodigoSUNAT = '008')

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

            $datos = DB::table('Caja')
            ->select(
                DB::raw("DATE_FORMAT(FechaInicio, '%d/%m/%Y') AS Fecha"),
                DB::raw("TIME(FechaInicio) AS Hora")
            )
            ->where('Codigo', $caja)
            ->where('Estado', 'A')
            ->first();

            $apertura = DB::table('IngresoDinero')
                ->where('CodigoCaja', $caja)
                ->where('Vigente', 1)
                ->where('Tipo', 'A')
                ->selectRaw('COALESCE(SUM(Monto), 0) as Apertura')
                ->limit(1)
                ->value('Apertura');
            
            // INGRESOS + APERTURA
            $ingresos = DB::table('IngresoDinero')
                ->where('CodigoCaja', $caja)
                ->where('Vigente', 1)
                ->selectRaw('COALESCE(SUM(Monto), 0) as Ingresos')
                ->value('Ingresos');
            
            // EGRESOS SIN SALIDA DE DINERO INTERNO
            $egresos = DB::table('Egreso as e')
                ->join('medioPago as mp', 'mp.Codigo', '=', 'e.CodigoMedioPago')
                ->where('e.CodigoCaja', $caja)
                ->where('mp.CodigoSUNAT', '008')
                ->where('e.Vigente', 1)
                ->whereNotIn('e.Codigo', function ($query) {
                    $query->select('Codigo')
                        ->from('salidadinero');
                })
                ->selectRaw('COALESCE(SUM(e.Monto), 0) AS Egreso')
                ->value('Egreso');
            
            // RECAUDACIÓN (PAGOS)
            $recaudacion = DB::table('Pago as pag')
                ->join('medioPago as mp', 'mp.Codigo', '=', 'pag.CodigoMedioPago')
                ->where('pag.CodigoCaja', $caja)
                ->where('pag.Vigente', 1)
                ->where('mp.CodigoSUNAT', '008')
                ->selectRaw('COALESCE(SUM(pag.Monto),0) as Recaudacion')
                ->value('Recaudacion');
            
            // SALIDAS (SALIDAS DE DINERO INTERNAS)
            $salida = DB::table('salidadinero as sdd')
                ->join('egreso as e', 'e.Codigo', '=', 'sdd.Codigo')
                ->join('medioPago as mp', 'mp.Codigo', '=', 'e.CodigoMedioPago')
                ->where('e.CodigoCaja', $caja)
                ->where('e.Vigente', 1)
                ->where('mp.CodigoSUNAT', '008')
                ->selectRaw('COALESCE(SUM(e.Monto), 0) AS Salida')
                ->value('Salida');
            
            // RESULTADOS
            $resultado = [
                'Datos' => $datos,
                'Apertura' => $apertura,
                'Ingresos' => $ingresos,
                'Egresos' => $egresos,
                'Recaudacion' => $recaudacion,
                'Salidas' => $salida
            ];
            
            return response()->json($resultado);
        
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

    public function registrarSalida(Request $request){
        $egreso = $request->input('Egreso');
        $salidaDinero = $request->input('SalidaDinero');
        DB::beginTransaction();
        try{

            //Validar Egreso
            $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();

            if (!isset($egreso['CodigoCuentaOrigen']) || !$egreso['CodigoCuentaOrigen']) {
                $egreso['CodigoCuentaOrigen'] = null;
            }
            
            if (!isset($salidaDinero['CodigoReceptor']) || !$salidaDinero['CodigoReceptor']) {
                $salidaDinero['CodigoReceptor'] = null;
            }

            if (!isset($salidaDinero['CodigoCuentaBancaria']) || !$salidaDinero['CodigoCuentaBancaria']) {
                $salidaDinero['CodigoCuentaBancaria'] = null;
            }

            $nuevoEgreso = Egreso::create($egreso);
            $salidaDinero['Codigo'] = $nuevoEgreso->Codigo;

            SalidaDinero::create($salidaDinero);
            DB::commit();
            return response()->json(['message' => 'Salida registrada correctamente'], 201);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la salida', 'error' => $e->getMessage()], 400);
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

    public function listarTrabajadoresSalidaDinero($sede){
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d');

        try{
        $trabajadores = DB::table('trabajadors as t')
            ->leftJoin('asignacion_sedes as ass', 'ass.CodigoTrabajador', '=', 't.Codigo')
            ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
            ->where('t.Tipo', 'A')
            ->where('t.Vigente', 1)
            ->where('p.Vigente', 1)
            ->where('ass.Vigente', 1)
            ->where('ass.CodigoSede', $sede)
            ->where('ass.FechaFin', '>=', $fecha)
            ->select(
                't.Codigo as Codigo',
                DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombre")
            )
            ->get();

            return response()->json($trabajadores);

        }catch(\Exception $e){
            return response()->json(['message' => 'Error al listar los trabajadores', 'error' => $e->getMessage()], 400);
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
