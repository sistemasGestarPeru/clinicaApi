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
        try {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

            $empresas = DB::table('clinica_db.users as u')
                ->join('clinica_db.personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
                ->join('clinica_db.trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('clinica_db.contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->join('clinica_db.empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->where('u.CodigoPersona', $codigoPersona)
                ->where('cl.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('e.Vigente', 1)
                ->where(function ($query) use ($fecha) {
                    $query->where(function ($q) use ($fecha) {
                        $q->where('cl.FechaInicio', '<=', $fecha)
                            ->where(function ($subQuery) use ($fecha) {
                                $subQuery->where('cl.FechaFin', '>=', $fecha)
                                    ->orWhereNull('cl.FechaFin');
                            });
                    });
                })
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->get();

            return response()->json($empresas);
        } catch (\Exception $e) {
            return response()->json('Error al obtener datos: ' . $e, 400);
        }
    }


    public function ConsultaSedesTrab($codigoPersona, $codigoEmpresa)
    {
        try {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

            $sedes = DB::table('clinica_db.users as u')
                ->join('clinica_db.personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
                ->join('clinica_db.trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('clinica_db.asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
                ->join('clinica_db.sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
                ->join('clinica_db.empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->where('u.CodigoPersona', $codigoPersona)
                ->where('e.Codigo', $codigoEmpresa)
                ->where('t.Vigente', 1)
                ->where('ase.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('e.Vigente', 1)
                ->where(function ($query) use ($fecha) {
                    $query->where(function ($q) use ($fecha) {
                        $q->where('ase.FechaInicio', '<=', $fecha)
                            ->where(function ($subQuery) use ($fecha) {
                                $subQuery->where('ase.FechaFin', '>=', $fecha)
                                    ->orWhereNull('ase.FechaFin');
                            });
                    });
                })
                ->select('s.Codigo as id', 's.Nombre as nombre')
                ->get();

            return response()->json($sedes);
        } catch (\Exception $e) {
            return response()->json('Error al obtener datos', 400);
        }
    }
}
