<?php

namespace App\Http\Controllers\API\Consultas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultasTrabajadorController extends Controller
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

    public function ConsultaEmpresasTrab($codigoPersona)
    {
        $empresas = DB::table('clinica_db.users as u')
            ->join('clinica_db.personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
            ->join('clinica_db.trabajadors as t', 't.Codigo', '=', 'p.Codigo')
            ->join('clinica_db.contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
            ->join('clinica_db.empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
            ->where('u.CodigoPersona', $codigoPersona)
            //->select('e.Codigo', 'e.Nombre as EmpresaNombre', 'p.Nombres as PersonaNombres', 'p.Codigo as PersonaCodigo')
            ->select('e.Codigo as id', 'e.Nombre as nombre')
            ->get();

        return response()->json($empresas);
    }

    public function ConsultaSedesTrab($codigoPersona, $codigoEmpresa)
    {
        $sedes = DB::table('clinica_db.users as u')
            ->join('clinica_db.personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
            ->join('clinica_db.trabajadors as t', 't.Codigo', '=', 'p.Codigo')
            ->join('clinica_db.asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
            ->join('clinica_db.sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
            ->join('clinica_db.empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
            ->where('u.CodigoPersona', $codigoPersona)
            ->where('e.Codigo', $codigoEmpresa)
            ->select('s.Codigo as id', 's.Nombre as nombre')
            //->select('s.Codigo as SedeCodigo', 's.Nombre as SedeNombre', 'p.Nombres as PersonaNombres', 'e.Nombre as EmpresaNombre', 'p.Codigo as PersonaCodigo')
            ->get();

        return response()->json($sedes);
    }
}
