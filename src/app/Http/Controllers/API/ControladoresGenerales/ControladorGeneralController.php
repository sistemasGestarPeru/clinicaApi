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

        try {
            $sedes = DB::table('clinica_db.sedesrec as sr')
                ->leftJoin('clinica_db.asignacion_sedes as asg', function ($join) {
                    $join->on('sr.Codigo', '=', 'asg.CodigoSede')
                        ->where('asg.CodigoTrabajador', 2);
                })
                ->where('sr.CodigoEmpresa', 5)
                ->where(function ($query) {
                    $query->whereNull('asg.CodigoSede')
                        ->orWhere(function ($query) {
                            $query->where('asg.Codigo', function ($subQuery) {
                                $subQuery->select('asg2.Codigo')
                                    ->from('clinica_db.asignacion_sedes as asg2')
                                    ->whereColumn('asg2.CodigoSede', 'sr.Codigo')
                                    ->where('asg2.CodigoTrabajador', 2)
                                    ->orderBy('asg2.Codigo', 'desc')
                                    ->limit(1);
                            })
                                ->where('asg.Vigente', 0);
                        });
                })
                ->orderBy('asg.Codigo', 'desc')
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
        try {
            $departamentos = DB::table('clinica_db.sedesrec as s')
                ->join('clinica_db.departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
                ->where('s.Codigo', $sede)
                ->where('s.Vigente', 1)
                ->where('d.Vigente', 1)
                ->select('s.CodigoDepartamento as CodigoDepartamento')
                ->get();

            return response()->json($departamentos);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTiposDocVenta()
    {
        try {
            $docVentas = DB::table('clinica_db.tipodocumentoventa')
                ->where('Vigente', 1)
                ->select('Codigo as Codigo', 'Nombre as Nombre')
                ->get();

            return response()->json($docVentas);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMedioPago()
    {
        try {
            $medioPago = DB::table('clinica_db.mediopago')
                ->where('Vigente', 1)
                ->select('Codigo as Codigo', 'Nombre as Nombre')
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
}
