<?php

namespace App\Http\Controllers\API\CategoriaProducto;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            // Log de éxito

            Log::info('Categoria Productos listados correctamente', [
                'cantidad' => count($categorias),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json($categorias, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al listar Categorias Producto', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
		        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
          
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

            if (!$categoria) {
                // Log del error específico
                    Log::warning('Error de validación al listar usuarios', [
                        'Codigo' => $Codigo
                    ]);
                return response()->json(['error' => 'Categoria Producto no encontrada'], 404);
            }
            // Log de éxito
            Log::info('Categoria Producto consultada correctamente', [
                'codigo' => $Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($categoria, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error inesperado al consultar Categoria Producto', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

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

            if (!$categoria) {
                // Log del error específico
                Log::warning('Categoria Producto no encontrada para actualizar', [
                    'Codigo' => $request->Codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Categoria Producto no encontrada'], 404);
            }
            // Log de éxito
            Log::info('Categoria Producto actualizada correctamente', [
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($categoria, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al actualizar Categoria Producto', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
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

            if (!$categoria) {
                // Log del error específico
                Log::warning('Error al registrar Categoria Producto', [
                    'Nombre' => $request->Nombre,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Error al registrar Categoria Producto'], 400);
            }

            return response()->json($categoria, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error inesperado al registrar Categoria Producto', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
