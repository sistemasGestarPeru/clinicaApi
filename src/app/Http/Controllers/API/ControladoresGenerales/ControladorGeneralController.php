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

    public function listarApp($codigoTrabajador){
        try{
            $resultado = DB::table('usuario_perfil as up')
            ->join('rol as r', 'up.CodigoRol', '=', 'r.Codigo')
            ->join('aplicacion as a', 'r.CodigoAplicacion', '=', 'a.Codigo')
            ->where('up.CodigoPersona', $codigoTrabajador)
            ->where('a.Vigente', 1)
            ->distinct()
            ->select('a.Nombre', 'a.URL')
            ->get();
        

            return response()->json($resultado,200);

        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function ConsultaEmpresasTrab($codigoTrabajador)
    {
        try {
            $empresas = DB::table('contrato_laborals as cl')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->where('cl.CodigoTrabajador', $codigoTrabajador)
                ->get();

            return response()->json($empresas, 200);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTipoDocIdentidad(){
        
        try{
            $documentos = DB::table('tipo_documentos')
            ->where('Vigente', 1)
            ->select('Codigo as Codigo', 'Siglas as Nombre', 'CodigoSUNAT')
            ->get();
    
            return response()->json($documentos, 200);
            
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }

    }

    //Listar Combo Empresas
    public function listarEmpresas()
    {
        try {
            $empresas = DB::table('empresas')
                ->where('Vigente', 1)
                ->select('Codigo as id', 'Nombre as nombre')
                ->get();

            return response()->json($empresas, 200);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }
    //Listar Combo Sedes - Empresas
    public function listarSedesEmpresas($codigoEmpresa)
    {
        try {
            $sedes = DB::table('sedesrec')
                ->where('CodigoEmpresa', $codigoEmpresa)
                ->select('Codigo as id', 'Nombre as nombre')
                ->get();

            return response()->json($sedes, 200);
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

            return response()->json($sedes, 200);
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
            $departamentos = DB::table('sedesrec as s')
                ->join('departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
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

    public function listarTiposDocVenta($sede, $tipo)
    {
        
        if(!$tipo){
            $tipo = 'V';
        }

        try {

                $docVentas = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->select('tdv.Codigo', 'tdv.Nombre', 'tdv.CodigoSUNAT')
                ->where('ldv.CodigoSede', $sede)
                ->where('tdv.Vigente', 1)
                ->where('ldv.Vigente', 1)
                ->when($tipo !== 'T', function ($query) use ($tipo) {
                    return $query->where('tdv.Tipo', $tipo);
                })
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
                ->select('mp.Codigo', 'mp.Nombre', 'mp.CodigoSUNAT')
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

    public function listarBilleterasDigitalesEmpresa($empresa){
        try{
            $result = DB::table('billeteradigital as bd')
            ->join('entidadbilleteradigital as ebd', 'ebd.Codigo', '=', 'bd.Codigo')
            ->where('bd.CodigoEmpresa', $empresa)
            ->where('bd.Vigente', 1)
            ->where('ebd.Vigente', 1)
            ->select('bd.Codigo', DB::raw("CONCAT(ebd.Nombre, ' - ', bd.Numero) AS Nombre"))
            ->get();
            return response()->json($result);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarCuentasBancariasEmpresa($empresa)
    {
        try {
            $result = DB::table('cuentabancaria as cb')
                ->join('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria')
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
            $result = DB::table('motivoanulacion')
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
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d');
        try{
            $trabajadores = DB::table('trabajadors as t')
            ->select('t.Codigo', 'p.Nombres', 'p.Apellidos')
            ->join('asignacion_sedes as ass', 'ass.CodigoTrabajador', '=', 't.Codigo')
            ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
            ->where('t.AutorizaDescuento', 1)
            ->where('t.Vigente', 1)
            ->where('p.Vigente', 1)
            ->where('t.tipo', 'A')
            ->where('ass.Vigente', 1)
            ->where('ass.CodigoSede', $sede)
            ->where(function($query) use ($fecha) {
                $query->whereNull('ass.FechaFin')
                      ->orWhere('ass.FechaFin', '>=', $fecha);
            })
            ->get();
        
        return response()->json($trabajadores);
        
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }

    }

    public function personal($sede){
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d');
        try{
            $trabajadores = DB::table('trabajadors as t')
            ->select('t.Codigo', 'p.Nombres', 'p.Apellidos')
            ->join('asignacion_sedes as ass', 'ass.CodigoTrabajador', '=', 't.Codigo')
            ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
            ->where('t.Vigente', 1)
            ->where('p.Vigente', 1)
            ->where('ass.Vigente', 1)
            ->where('ass.CodigoSede', $sede)
            ->where('ass.FechaFin', '>=', $fecha)
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
        $fecha = date('Y-m-d');
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
            $resultado = DB::table('personas as p')
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

    public function listarMotivoAnulacionContrato(){
        try{
            $motivos = DB::table('motivoanulacioncontrato')
            ->where('Vigente', 1)
            ->select('Codigo', 'Nombre', 'Descripcion')
            ->get();
            return response()->json($motivos);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarDonantes(){
        try{
            $personas = DB::table('personas')
            ->select(
                'Codigo',
                DB::raw("CONCAT(Nombres, ' ', Apellidos) as Nombres")
            )
            ->where('Vigente', 1)
            ->get();

            return response()->json($personas);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }   
    }


    public function listarCategoriaProducto(){
        try{
            $categoria = DB::table('categoriaproducto')
            ->select(
                'Codigo',
                'Nombre'
            )
            ->where('Vigente', 1)
            ->get();

            return response()->json($categoria);
        }catch(\Exception $e){
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }   
    }

}
