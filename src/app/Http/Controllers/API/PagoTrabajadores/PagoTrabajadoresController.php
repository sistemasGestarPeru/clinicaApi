<?php

namespace App\Http\Controllers\API\PagoTrabajadores;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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


    public function listarTrabajadoresPlanilla(Request $request)
    {
        $empresa = $request->input('empresa');

        try {
            $resultado = DB::table('personas as p')
                ->join('trabajadors as t', 't.Codigo', '=', 'p.Codigo')
                ->join('SistemaPensiones as sp', 'sp.Codigo', '=', 't.CodigoSistemaPensiones')
                ->join('contrato_laborals as cl', 'cl.CodigoTrabajador', '=', 't.Codigo')
                ->select(
                    'p.Nombres',
                    'p.Apellidos',
                    'sp.Nombre as Pension',
                    'cl.SueldoBase'
                )
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('cl.Vigente', 1)
                ->where('cl.CodigoEmpresa', $empresa)
                ->orderBy('p.Codigo')
                ->get();

            return response()->json($resultado, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Ocurrió un error al listar trabajadores'], 500);
        }
    }
}
