<?php

namespace App\Http\Controllers\API\Consultas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $empresas = DB::table('users as u')
                ->join('personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
                ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
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

            // Log de éxito
            Log::info('Empresas de trabajador consultadas correctamente', [
                'Controlador' => 'ConsultasTrabajadorController',
                'Metodo' => 'ConsultaEmpresasTrab',
                'codigoPersona' => $codigoPersona,
                'cantidad' => count($empresas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($empresas);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al consultar empresas de trabajador', [
                'Controlador' => 'ConsultasTrabajadorController',
                'Metodo' => 'ConsultaEmpresasTrab',
                'codigoPersona' => $codigoPersona,
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error al obtener datos: ' . $e, 400);
        }
    }


    public function ConsultaSedesTrab($codigoPersona, $codigoEmpresa)
    {
        try {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

            $sedes = DB::table('users as u')
                ->join('personas as p', 'u.CodigoPersona', '=', 'p.Codigo')
                ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
                ->join('sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
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

            // Log de éxito
            Log::info('Sedes de trabajador consultadas correctamente', [
                'Controlador' => 'ConsultasTrabajadorController',
                'Metodo' => 'ConsultaSedesTrab',
                'codigoPersona' => $codigoPersona,
                'codigoEmpresa' => $codigoEmpresa,
                'cantidad' => count($sedes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($sedes);
        } catch (\Exception $e) {
            // Log de error
            Log::error('Error al consultar sedes de trabajador', [
                'Controlador' => 'ConsultasTrabajadorController',
                'Metodo' => 'ConsultaSedesTrab',
                'codigoPersona' => $codigoPersona,
                'codigoEmpresa' => $codigoEmpresa,
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error al obtener datos', 400);
        }
    }
}
