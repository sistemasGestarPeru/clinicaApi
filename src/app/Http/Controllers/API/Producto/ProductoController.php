<?php

namespace App\Http\Controllers\API\Producto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\RegistarProductoRequest;
use App\Models\Recaudacion\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
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


    public function listarProducto(Request $request)
    {
        try {

            $productos = DB::table('producto as p')
                ->join('categoriaproducto as cp', 'cp.Codigo', '=', 'p.CodigoCategoria')
                ->select(
                    'p.Codigo',
                    'cp.Nombre as Categoria',
                    'p.Nombre as Producto',
                    DB::raw("CASE 
                    WHEN p.Tipo = 'S' THEN 'Servicio'
                    WHEN p.Tipo = 'B' THEN 'Bien'
                    ELSE 'Desconocido'
                END AS Tipo")
                )
                ->get();

            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarProducto(RegistarProductoRequest $request)
    {
        $producto = $request->all();

        try {

            Producto::create($producto);
            return response()->json(['message' => 'Producto registrado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }





    //PRECIO TEMPORAL

    public function preciosTemporales($sede, $producto)
    {
        try {
            $productos = DB::table('preciotemporal')
                ->select('Codigo AS Temporal', 'Precio', 'Stock')
                ->where('Vigente', 1)
                ->where('CodigoSede', $sede)
                ->where('CodigoProducto', $producto)
                ->where('Stock', '>', 0)
                ->get();
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
