<?php

namespace App\Http\Controllers\API\Almacen\Lote;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoteController extends Controller
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

    public function listarGuiasIngreso($sede){
        try{
            $resultados = DB::table('guiaingreso')
            ->where('Vigente', 1)
            ->where('CodigoSede', $sede)
            ->selectRaw('Codigo, CONCAT(Serie, "-", Numero) as DocGuia')
            ->get();
            return response()->json($resultados, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar Guías de Ingreso', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarDetallesGuia($codigo){
        try{

            $resultados = DB::table('detalleguiaingreso as dgi')
            ->join('producto as p', 'p.Codigo', '=', 'dgi.CodigoProducto')
            ->leftJoin(DB::raw('(
                SELECT 
                    CodigoProducto,
                    CodigoDetalleIngreso,
                    SUM(Cantidad) as Cantidad,
                    SUM(Costo) as Costo
                FROM lote 
                GROUP BY CodigoProducto, CodigoDetalleIngreso
            ) AS LOTEREG'), function ($join) {
                $join->on('LOTEREG.CodigoProducto', '=', 'dgi.CodigoProducto')
                     ->on('LOTEREG.CodigoDetalleIngreso', '=', 'dgi.Codigo');
            })
            ->where('dgi.CodigoGuiaRemision', $codigo)
            ->whereRaw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad,0)) > 0')
            ->select('dgi.Codigo', 'p.Nombre')
            ->get();
        

            return response()->json($resultados, 200);

        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar Detalle de Guía', 'bd' => $e->getMessage()], 500);
        }
    }

    public function detallexGuia($codigo){
        try{
            $resultados = DB::table('detalleguiaingreso as dgi')
            ->leftJoin(DB::raw('(
                SELECT 
                    CodigoProducto,
                    SUM(Cantidad) as Cantidad,
                    SUM(Costo) as Costo
                FROM lote 
                WHERE CodigoDetalleIngreso = '.$codigo.'
                GROUP BY CodigoProducto
            ) AS LOTEREG'), function ($join) {
                $join->on('LOTEREG.CodigoProducto', '=', 'dgi.CodigoProducto');
            })
            ->join('sedeproducto as sp', 'dgi.CodigoProducto', '=', 'sp.CodigoProducto')
            ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
            ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
            ->where('dgi.Codigo', $codigo)
            ->whereRaw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad,0)) > 0')
            ->select(
                'dgi.Codigo',
                DB::raw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad,0)) as Cantidad'),
                DB::raw('(dgi.Costo - COALESCE(LOTEREG.Costo,0)) as Costo'),
                'dgi.CodigoProducto',
                'tg.Porcentaje'
            )
            ->first();
            return response()->json($resultados, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar Detalle de Guía', 'bd' => $e->getMessage()], 500);
        }
    }
}
