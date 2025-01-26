<?php

namespace App\Http\Controllers\API\ControladoresGenerales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControladorGeneralController extends Controller
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

    public function ConsultaEmpresasTrab($codigoTrabajador)
    {
        try {
            $empresas = DB::table('contrato_laborals as cl')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->where('cl.CodigoTrabajador', $codigoTrabajador)
                ->get();

            return response()->json($empresas);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTipoDocIdentidad(){
        
        try{
            $documentos = DB::table('clinica_db.tipo_documentos')
            ->where('Vigente', 1)
            ->select('Codigo as Codigo', 'Siglas as Nombre', 'CodigoSUNAT')
            ->get();
    
            return response()->json($documentos);
            
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }

    }

    //Listar Combo Empresas
    public function listarEmpresas()
    {
        try {
            $empresas = DB::table('clinica_db.empresas')
                ->where('Vigente', 1)
                ->select('Codigo as id', 'Nombre as nombre')
                ->get();

            return response()->json($empresas);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }
    //Listar Combo Sedes - Empresas
    public function listarSedesEmpresas($codigoEmpresa)
    {
        try {
            $sedes = DB::table('clinica_db.sedesrec')
                ->where('CodigoEmpresa', $codigoEmpresa)
                ->select('Codigo as id', 'Nombre as nombre')
                ->get();

            return response()->json($sedes);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cboSedesDisponibles($codigoEmpresa, $codigoTrabajador)
    {
        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d'); 
        try {
            $sedes = DB::table('sedesrec as s')
            ->select('s.Codigo as id', 's.Nombre as nombre')
            ->leftJoin('asignacion_sedes as ass', function ($join) use ($codigoTrabajador, $fechaActual) {
                $join->on('s.Codigo', '=', 'ass.CodigoSede')
                     ->where('ass.Codigo', '=', DB::raw("(SELECT MAX(Codigo)
                                                          FROM asignacion_sedes
                                                          WHERE CodigoSede = s.Codigo
                                                          AND CodigoTrabajador = {$codigoTrabajador}
                                                          AND Vigente = 1)"))
                     ->where(function ($query) use ($fechaActual) {
                         $query->where('ass.FechaFin', '>', $fechaActual)
                               ->orWhereNull('ass.FechaFin');
                     });
            })
            ->where('s.CodigoEmpresa', $codigoEmpresa)
            ->whereNull('ass.Codigo')
            ->get();

            return response()->json($sedes);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cboEmpresasDisponibles($codigoTrabajador)
{
    date_default_timezone_set('America/Lima');
    $fechaActual = date('Y-m-d'); 

    try {
        $empresas = DB::table('empresas as e')
        ->select('e.Codigo as id', 'e.Nombre as nombre')
        ->leftJoin('contrato_laborals as cl', function ($join) use ($fechaActual, $codigoTrabajador) {
            $join->on('e.Codigo', '=', 'cl.CodigoEmpresa')
                 ->where('cl.Codigo', '=', DB::raw("(SELECT MAX(Codigo)
                                                      FROM contrato_laborals
                                                      WHERE CodigoEmpresa = e.Codigo
                                                      AND CodigoTrabajador = {$codigoTrabajador}
                                                      AND Vigente = 1)"))
                 ->where(function ($query) use ($fechaActual) {
                     $query->where('cl.FechaFin', '>', $fechaActual)
                           ->orWhereNull('cl.FechaFin');
                 });
        })
        ->where('e.Vigente', 1)
        ->whereNull('cl.Codigo')
        ->get();

        return response()->json($empresas);
    } catch (\Exception $e) {
        return response()->json('Error en la consulta: ' . $e->getMessage());
    }
}

    public function listarDepartamentos($sede)
    {
        try {
            $departamentos = DB::table('clinica_db.sedesrec as s')
                ->join('clinica_db.departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
                ->where('s.Codigo', $sede)
                ->where('s.Vigente', 1)
                ->where('d.Vigente', 1)
                ->select('s.CodigoDepartamento as CodigoDepartamento')
                ->first();

            return response()->json($departamentos);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTiposDocVenta($sede)
    {
        try {

            $docVentas = DB::table('localdocumentoventa as ldv')
                ->join('sedesrec as s', 's.Codigo', '=', 'ldv.CodigoSede')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->select('tdv.Codigo', 'tdv.Nombre', 'tdv.CodigoSUNAT')
                ->where('ldv.CodigoSede', $sede)
                ->where('tdv.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('ldv.Vigente', 1)
                ->distinct()
                ->get();

            return response()->json($docVentas);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarSistemaPension(){
        try {
            $sistemaPension = DB::table('sistemapensiones as sp')
                ->where('sp.Vigente', 1)
                ->select('sp.Codigo', 'sp.Siglas as Nombre')
                ->get();
            return response()->json($sistemaPension);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMedioPago($sede)
    {
        try {
            $medioPago = DB::table('localmediopago as lmp')
                ->join('sedesrec as s', 's.Codigo', '=', 'lmp.CodigoSede')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'lmp.CodigoMedioPago')
                ->select('mp.Codigo', 'mp.Nombre')
                ->where('lmp.CodigoSede', $sede)
                ->where('mp.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('lmp.Vigente', 1)
                ->get();
            return response()->json($medioPago);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }


    public function listarCuentasBancariasEmpresa($empresa)
    {
        try {
            $result = DB::table('clinica_db.cuentabancaria as cb')
                ->join('clinica_db.EntidadBancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria')
                ->where('cb.CodigoEmpresa', $empresa)
                ->where('cb.Vigente', 1)
                ->where('eb.Vigente', 1)
                ->where('cb.Detraccion', 0)
                ->select('cb.Codigo as Codigo', 'eb.Siglas', 'cb.Numero')
                ->get();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMotivosAnulacion()
    {
        try {
            $result = DB::table('clinica_db.motivoanulacion')
                ->where('Vigente', 1)
                ->select('Codigo as Codigo', 'Nombre as Nombre', 'Descripcion as Descripcion')
                ->get();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cuentaDetraccion($empresa)
    {

        try {

            $resultado = DB::table('cuentabancaria as cb')
                ->select('cb.Codigo', 'eb.Nombre', 'eb.Siglas', 'cb.Numero', 'cb.CCI')
                ->join('empresas as e', 'e.Codigo', '=', 'cb.CodigoEmpresa') // Relación con empresas
                ->join('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria') // Relación con entidad bancaria
                ->where('cb.Detraccion', 1) // Filtro por Detracción
                ->where('cb.Vigente', 1) // Filtro por Vigencia de cuenta bancaria
                ->where('e.Codigo', $empresa) // Filtro por Código de Empresa
                ->where('e.Vigente', 1) // Filtro por Vigencia de Empresa
                ->where('eb.Vigente', 1) // Filtro por Vigencia de Entidad Bancaria
                ->first();

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }


    public function listarMotivoPagoServicio(){
        try {

            $resultado = DB::table('motivopagoservicio')
                ->select('Codigo', 'Nombre', 'Descripcion')
                ->where('Vigente', 1) // 
                ->get();

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }

    }

    public function personalAutorizado($sede){
        
        try{

            $trabajadores = DB::table('trabajadors as t')
            ->select('t.Codigo', 'p.Nombres', 'p.Apellidos')
            ->join('asignacion_sedes as ass', 'ass.CodigoTrabajador', '=', 't.Codigo')
            ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
            ->where('t.AutorizaDescuento', 1)
            ->where('t.Vigente', 1)
            ->where('p.Vigente', 1)
            ->where('ass.Vigente', 1)
            ->where('ass.CodigoSede', $sede)
            ->get();
            return response()->json($trabajadores);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }

    }

    public function listarTipoMoneda(){
        try{
            $resp = DB::table('tipomoneda')
            ->select('Codigo', 'Nombre', 'Siglas')
            ->where('Vigente', 1)
            ->get();

            return response()->json($resp);

        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMedicos($sede){ //Para Contrato y Ventas
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');
        try{

            $resultado = DB::table('trabajadors as t')
                ->join('asignacion_sedes as ags', 'ags.CodigoTrabajador', '=', 't.Codigo')
                ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
                ->select('t.Codigo as Codigo', 'p.Apellidos', 'p.Nombres')
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('ags.Vigente', 1)
                ->where(function ($query) use ($fecha) {
                    $query->where('ags.FechaFin', '>=', $fecha)
                        ->orWhereNull('ags.FechaFin');
                })
                ->where('ags.CodigoSede', $sede)
                ->where('t.Tipo', 'M')
                ->get();

            return response()->json($resultado);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarPacientes($sede){
        try{
            $resultado = DB::table('Personas as p')
                ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'p.CodigoDepartamento')
                ->select('p.Codigo', 'p.Nombres', 'p.Apellidos')
                ->where('p.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('s.Codigo', $sede)
                ->get();
            return response()->json($resultado);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMotivoNotaCredito(){
        try{
            $resultado = DB::table('motivonotacredito')
                ->select('Codigo', 'Nombre', 'CodigoSUNAT')
                ->where('Vigente', 1)
                ->get();
            return response()->json($resultado);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

}
