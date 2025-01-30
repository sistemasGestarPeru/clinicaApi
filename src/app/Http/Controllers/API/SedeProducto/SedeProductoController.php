<?php

namespace App\Http\Controllers\API\SedeProducto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SedeProductoController extends Controller
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


    public function listarSedeProducto(Request $request){
        try{
            $productos = DB::table('sedeproducto as sp')
            ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
            ->join('sedesrec as s', 's.Codigo', '=', 'sp.CodigoSede')
            ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
            ->select([
                'sp.Codigo',
                's.Nombre as Sede',
                'p.Nombre as Producto',
                'sp.Precio',
                'sp.Stock',
                'tg.Tipo as TipoGravado',
                'tg.Nombre as NombreGravado'
            ])
            ->get();

            return response()->json($productos, 200);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarProductoSede(Request $request){
        try{

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
