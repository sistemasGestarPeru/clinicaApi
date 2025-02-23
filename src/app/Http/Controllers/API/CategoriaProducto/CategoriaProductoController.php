<?php

namespace App\Http\Controllers\API\CategoriaProducto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaProductoController extends Controller
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

    public function listarCategoriaProducto()
    {
        try {
            $categorias = DB::table('categoriaproducto')
                ->select('Codigo', 'Nombre', 'Descripcion', 'Vigente')
                ->get();
            return response()->json($categorias, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarCategoriaProducto($Codigo)
    {
        try {
            $categoria = DB::table('categoriaproducto')
                ->select('Codigo', 'Nombre', 'Descripcion', 'Vigente')
                ->where('Codigo', $Codigo)
                ->first();
            return response()->json($categoria, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarCategoriaProducto(Request $request)
    {
        $categoria = $request->all();

        try {
            $categoria = DB::table('categoriaproducto')
                ->where('Codigo', $categoria['Codigo'])
                ->update([
                    'Nombre' => $request->Nombre,
                    'Descripcion' => $request->Descripcion,
                    'Vigente' => $request->Vigente
                ]);
            return response()->json($categoria, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarCategoriaProducto(Request $request)
    {
        $categoria = $request->all();

        try {
            $categoria = DB::table('categoriaproducto')
                ->insert([
                    'Nombre' => $request->Nombre,
                    'Descripcion' => $request->Descripcion,
                    'Vigente' => $request->Vigente
                ]);
            return response()->json($categoria, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
