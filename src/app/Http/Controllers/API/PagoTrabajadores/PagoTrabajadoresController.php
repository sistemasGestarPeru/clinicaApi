<?php

namespace App\Http\Controllers\API\PagoTrabajadores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\PagoTrabajadores\PagoPlanilla;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagoTrabajadoresController extends Controller
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


    public function consultarPagoTrabajador($codigo, $empresa){
        try{
            $pagoTrabajador = PagoPlanilla::where('Codigo', $codigo)->first();
            $egreso = Egreso::where('Codigo', $codigo)->first();


            $trabajador = DB::table('trabajadors as t')
            ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
            ->join('sistemapensiones as sp', 'sp.Codigo', '=', 't.CodigoSistemaPensiones')
            ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
            ->select(
                'p.Codigo',
                'p.Nombres',
                'p.Apellidos',
                'p.NumeroDocumento',
                'cl.SueldoBase',
                'sp.Nombre as Pension',
            )
            ->where('t.Codigo', $pagoTrabajador->CodigoTrabajador)
            ->where('cl.CodigoEmpresa', $empresa)
            ->orderByDesc('cl.Codigo')
            ->first();

            return response()->json(['pagoTrabajador' => $pagoTrabajador, 'egreso' => $egreso, 'trabajador' => $trabajador], 200);

        }catch(Exception $e){
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al consultar el pago del trabajador'], 500);
        }
    }

    public function listarPagosRealizados(Request $request)
    {
        $filtro = $request->input('nombre');
        $fecha = $request->input('mes');
    
        try {

            $query = DB::table('pagopersonal as pp')
                ->join('personas as p', 'p.Codigo', '=', 'pp.CodigoTrabajador')
                ->join('egreso as e', 'e.Codigo', '=', 'pp.Codigo')
                ->select(
                    'pp.Codigo',
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres"),
                    DB::raw("DATE_FORMAT(pp.Mes, '%m/%Y') AS Mes"),
                    'e.Vigente'
                );
            
            // Condición opcional por fecha (si existe)
            if (!empty($fecha)) {
                $query->whereRaw("DATE_FORMAT(pp.Mes, '%Y-%m') = ?", [$fecha]);
            }
            
            // Condición opcional por filtro de nombre o apellido (si existe)
            if (!empty($filtro)) {
                $query->where(function ($q) use ($filtro) {
                    $q->where('p.Nombres', 'LIKE', "$filtro%")
                    ->orWhere('p.Apellidos', 'LIKE', "$filtro%");
                });
            }
            
            $resultados = $query->get();
            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            return response()->json(['bd' => $e->getMessage(), 'error' => 'Ocurrió un error al listar los pagos realizados'], 500);
        }
    }

    public function actualizarPagoIndividual(Request $request){
        $egreso = request()->input('egreso');
        DB::beginTransaction();
        try{

            $estadoCaja = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);

            if ($estadoCaja->Estado == 'C') {
                return response()->json([
                    'error' => __('mensajes.error_act_egreso_caja', ['tipo' => 'trabajador']),
                ], 400);
            }

            $egresoData = Egreso::find($egreso['Codigo']);

            if (!$egresoData) {
                return response()->json([
                    'error' => 'No se ha encontrado el Pago Trabajador.'
                ], 404);
            }

            if ($egresoData['Vigente'] == 1) {
                $egresoData->update(['Vigente' => $egreso['Vigente']]);
            } else {
                return response()->json([
                    'error' => __('mensajes.error_act_egreso', ['tipo' => 'trabajador']),
                ], 400);
            }

            DB::commit();
            return response()->json([
                'message' => 'Pago trabajador actualizado correctamente.'
            ], 200);

        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['bd' => $e->getMessage(), 'error' => 'Ocurrió un error al actualizar el pago'], 500);
        }
    }

    public function registrarPagoIndividual(Request $request){

        date_default_timezone_set('America/Lima');
        $fechaActual = date('d');

        $trabajador = $request->input('trabajador');
        $egreso = $request->input('egreso');

        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();

        $trabajadorValidator = Validator::make($trabajador, [
            'CodigoSistemaPensiones' => 'required|integer',
            'CodigoTrabajador' => 'required|integer',
            'Mes' => 'required|date',
            //'MontoPension' => 'required|integer',
            // 'MontoSeguro' => 'required|integer',
            // 'ReciboHonorario' => 'nullable|integer',
        ]);
        if ($trabajadorValidator->fails()) {
            return response()->json([
                'error' => $trabajadorValidator->errors(),
                'mensaje' => "Error al registrar datos del pago"
            ], 400);
        }


        if(isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0){
            $egreso['CodigoCuentaOrigen'] = null;
        }

        if(isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0){
            $egreso['CodigoBilleteraDigital'] = null;
        }


        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
        }

        if ($egreso['CodigoSUNAT'] == '008') {
            $egreso['CodigoCuentaOrigen'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
            $egreso['NumeroOperacion'] = null;

            $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

            if($egreso['Monto'] > $total){
                return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
            }

        }else if($egreso['CodigoSUNAT'] == '003'){
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;

        }else if($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006'){
            $egreso['CodigoCuentaBancaria'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
        }

        DB::beginTransaction();
        try {
                $nuevoEgresoId = Egreso::create($egreso)->Codigo;

                $trabajador['Mes'] = $trabajador['Mes'] . '-' . $fechaActual;
                $trabajador['Codigo'] = $nuevoEgresoId;
                PagoPlanilla::create($trabajador);
            
            DB::commit();
            return response()->json(['mensaje' => 'Pago Trabajador registrada correctamente'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['bd' => $e->getMessage(), 'error' => 'Ocurrió un error al registrar el pago Trabajador'], 500);
        }

    }

    public function registrarPlanilla(Request $request)
    {

        date_default_timezone_set('America/Lima');
        $fechaActual = date('d');

        $planillas = $request->input('planilla');
        $egreso = $request->input('egreso');


        if (empty($planillas) || empty($egreso)) {
            return response()->json(['mensaje' => 'No se han enviado datos'], 400);
        }

        foreach ($egreso as $index => $egresos) {

            $egresoValidator = Validator::make($egresos, [
                'Fecha' => 'required|date',
                'Monto' => 'required|integer',
                'CodigoCaja' => 'required|integer',
                'CodigoTrabajador' => 'required|integer',
                'CodigoMedioPago' => 'required|integer',
            ]);

            if ($egresoValidator->fails()) {
                return response()->json([
                    'error' => $egresoValidator->errors(),
                    'mensaje' => "Error en el trabajador del índice $index "
                ], 400);
            }
        }

        // Validar cada objeto dentro del arreglo
        foreach ($planillas as $index => $planilla) {
            $planillaValidator = Validator::make($planilla, [
                'CodigoSistemaPensiones' => 'required|integer',
                'CodigoTrabajador' => 'required|integer',
                'Mes' => 'required|date',
                //'MontoPension' => 'required|integer',
                // 'MontoSeguro' => 'required|integer',
                // 'ReciboHonorario' => 'nullable|integer',
            ]);
            if ($planillaValidator->fails()) {
                return response()->json([
                    'error' => $planillaValidator->errors(),
                    'mensaje' => "Error en la planilla del índice " . ($index + 1)
                ], 400);
            }
        }



        DB::beginTransaction();
        try {

            foreach ($egreso as $index => $egresos) {

                if(isset($egresos['CodigoCuentaOrigen']) && $egresos['CodigoCuentaOrigen'] == 0){
                    $egresos['CodigoCuentaOrigen'] = null;
                }
        
                if(isset($egresos['CodigoBilleteraDigital']) && $egresos['CodigoBilleteraDigital'] == 0){
                    $egresos['CodigoBilleteraDigital'] = null;
                }
        
                if ($egresos['CodigoSUNAT'] == '008') {
                    $egresos['CodigoCuentaOrigen'] = null;
                    $egresos['CodigoBilleteraDigital'] = null;
                    $egresos['Lote'] = null;
                    $egresos['Referencia'] = null;
                    $egresos['NumeroOperacion'] = null;
        
                }else if($egresos['CodigoSUNAT'] == '003'){
                    $egresos['Lote'] = null;
                    $egresos['Referencia'] = null;
        
                }else if($egresos['CodigoSUNAT'] == '005' || $egresos['CodigoSUNAT'] == '006'){
                    $egresos['CodigoCuentaBancaria'] = null;
                    $egresos['CodigoBilleteraDigital'] = null;
                }

                $nuevoEgresoId = Egreso::create($egresos)->Codigo;

                $planilla = $planillas[$index];

                $planilla['Mes'] = $planilla['Mes'] . '-' . $fechaActual;

                $planilla['Codigo'] = $nuevoEgresoId;

                PagoPlanilla::create($planilla);
            }
            DB::commit();
            return response()->json(['mensaje' => 'Planilla registrada correctamente'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al registrar la planilla'], 500);
        }
    }


    public function listarTrabajadoresPlanilla(Request $request)
    {
        $empresa = $request->input('empresa');
        $fecha = $request->input('fecha');
        
        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d');

        try {
            $resultado = DB::table('personas as p')
                ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('sistemapensiones as sp', 'sp.Codigo', '=', 't.CodigoSistemaPensiones')
                ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->select(
                    't.Codigo as CodigoTrabajador',
                    'p.Nombres',
                    'p.Apellidos',
                    'sp.Nombre as Pension',
                    'sp.Codigo as CodigoSistemaPensiones',
                    'cl.SueldoBase'
                )
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('cl.Vigente', 1)
                ->where('cl.CodigoEmpresa', $empresa)
                ->where(function ($query) use ($fechaActual) { // Usar use para pasar $fechaActual
                    $query->whereNull('cl.FechaFin')
                        ->orWhere('cl.FechaFin', '>', $fechaActual);
                })
                ->whereNotIn('cl.CodigoTrabajador', function ($query) use ($fecha) {
                    $formattedFecha = date('Y-m', strtotime($fecha)); // Formatear la fecha como YYYY-MM
                    $query->select('PP.CodigoTrabajador')
                        ->from('pagopersonal as PP')
                        ->join('egreso as E', 'PP.Codigo', '=', 'E.Codigo')
                        ->where('E.Vigente', 1)
                        ->whereRaw("DATE_FORMAT(PP.Mes, '%Y-%m') = ?", [$formattedFecha]);
                })
                ->orderBy('p.Codigo')
                ->get();

            return response()->json($resultado, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al listar trabajadores'], 500);
        }
    }

    public function buscarTrabajadorPago(Request $request)
    {

        $empresa = $request->input('empresa');
        $tipoDocumento = $request->input('tipoDocumento');
        $numeroDocumento = $request->input('numeroDocumento');
        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d'); 
        $fecha = $request->input('mesSeleccionado');

        try {

            $resultado = DB::table('trabajadors as t')
            ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
            ->join('SistemaPensiones as sp', 'sp.Codigo', '=', 't.CodigoSistemaPensiones')
            ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
            ->select(
                'p.Codigo',
                'p.Nombres',
                'p.Apellidos',
                'p.NumeroDocumento',
                'cl.SueldoBase',
                'sp.Nombre as Pension',
                'sp.Codigo as CodigoSistemaPensiones'
            )
            ->where('p.Vigente', 1)
            ->where('t.Vigente', 1)
            ->where('cl.Vigente', 1)
            ->where('cl.CodigoEmpresa', $empresa)
            ->where('p.CodigoTipoDocumento', $tipoDocumento)
            ->where('p.NumeroDocumento', $numeroDocumento)
            ->where(function ($query) use ($fechaActual) {
                $query->whereNull('cl.FechaFin')
                      ->orWhere('cl.FechaFin', '>', $fechaActual);
            })
            ->whereNotIn('cl.CodigoTrabajador', function ($query) use ($fecha) {
                $formattedFecha = date('Y-m', strtotime($fecha)); // Formatear la fecha como YYYY-MM
                $query->select('PP.CodigoTrabajador')
                      ->from('pagopersonal as PP')
                      ->join('egreso as E', 'PP.Codigo', '=', 'E.Codigo')
                      ->where('E.Vigente', 1)
                      ->whereRaw("DATE_FORMAT(PP.Mes, '%Y-%m') = ?", [$formattedFecha]);
            })
            ->limit(1)
            ->first();

            return response()->json($resultado, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al buscar el trabajador'], 500);
        }
    }
}
