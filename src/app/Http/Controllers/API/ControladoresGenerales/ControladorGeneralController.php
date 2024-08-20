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

    //Listar Combo Empresas
    public function listarEmpresas()
    {
        $empresas = DB::table('clinica_db.empresas')
            ->where('Vigente', 1)
            ->select('Codigo as id', 'Nombre as nombre')
            ->get();

        return response()->json($empresas);
    }
    //Listar Combo Sedes - Empresas
    public function listarSedesEmpresas($codigoEmpresa)
    {
        $sedes = DB::table('clinica_db.sedesrec')
            ->where('CodigoEmpresa', $codigoEmpresa)
            ->select('Codigo as id', 'Nombre as nombre')
            ->get();

        return response()->json($sedes);
    }

    public function cboSedesDisponibles($codigoEmpresa, $codigoTrabajador)
    {

        try {
            $sedes = DB::table('clinica_db.sedesrec as sr')
                ->leftJoin('clinica_db.asignacion_sedes as asg', function ($join) use ($codigoTrabajador) {
                    $join->on('sr.Codigo', '=', 'asg.CodigoSede')
                        ->where('asg.CodigoTrabajador', '=', $codigoTrabajador);
                })
                ->where('sr.CodigoEmpresa', $codigoEmpresa)
                ->whereNull('asg.CodigoSede')
                ->select('sr.Codigo as id', 'sr.Nombre as nombre')
                ->get();

            return response()->json($sedes);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cboEmpresasDisponibles($codigoTrabajador)
    {
        try {

            $empresas = DB::table('clinica_db.empresas as e')
                ->leftJoin('clinica_db.contrato_laborals as cl', function ($join) use ($codigoTrabajador) {
                    $join->on('e.Codigo', '=', 'cl.CodigoEmpresa')
                        ->where('cl.CodigoTrabajador', '=', $codigoTrabajador);
                })
                ->whereNull('cl.CodigoTrabajador')
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->get();

            return response()->json($empresas);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarDepartamentos($sede)
    {
        $departamentos = DB::table('clinica_db.sedesrec as s')
            ->join('clinica_db.departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
            ->where('s.Codigo', $sede)
            ->where('s.Vigente', 1)
            ->where('d.Vigente', 1)
            ->select('s.CodigoDepartamento as CodigoDepartamento')
            ->get();

        return response()->json($departamentos);
    }

    public function listarTiposDocVenta()
    {
        $docVentas = DB::table('clinica_db.tipodocumentoventa')
            ->where('Vigente', 1)
            ->select('Codigo as Codigo', 'Nombre as Nombre')
            ->get();

        return response()->json($docVentas);
    }


    // SELECT s.CodigoDepartamento 
    // FROM sedesrec as s
    // INNER JOIN departamentos as d ON d.Codigo = s.CodigoDepartamento
    // WHERE s.Codigo = 1;

    //Listar Tipo de Documento
    //Listar Nacionalidad
    //Listar Departamento
}
