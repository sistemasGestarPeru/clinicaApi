<?php

namespace App\Http\Controllers\API\Almacen\Transformacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransformacionController extends Controller
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

    public function listarProductosDisponibles($sede){
        try{
            $productos = DB::table('sedeproducto as sp')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->select('sp.CodigoProducto', 'p.Nombre', 'sp.Stock', 'sp.CostoCompraPromedio')
                ->where('p.Tipo', 'B')
                // ->where('sp.Stock', '>', 0)
                ->where('sp.Vigente', 1)
                ->where('sp.CodigoSede', $sede)
                ->get();
            return response()->json($productos, 200);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al listar los productos disponibles',
                'bd' => $e->getMessage()
            ], 500);
        }
    }
}
