<?php

namespace App\Http\Controllers\API\Moneda;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Moneda;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonedaController extends Controller
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

    public function listarMoneda()
    {
        try {
            $entidad = Moneda::all();
            Log::info('Listar Monedas', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'listarMoneda',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            Log::error('Error al listar Monedas', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'listarMoneda',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMoneda(Request $request)
    {
        try {
            Moneda::create($request->all());
            Log::info('Registrar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'registrarMoneda',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Unidad de Medida registrada correctamente'], 200);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                Log::warning('Error al registrar Moneda: Clave duplicada', [
                    'Controlador' => 'MonedaController',
                    'Metodo' => 'registrarMoneda',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'error' => 'El nombre o siglas de la Moneda ya existe.'
                ], 500);
            }
            Log::error('Error al registrar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'registrarMoneda',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error al registar la Moneda.'
            ], 500);
        } catch (\Exception $e) {

            Log::error('Error al registrar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'registrarMoneda',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => 'Ocurrió un error al registar la Moneda.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarMoneda($codigo)
    {
        try {
            $entidad = Moneda::find($codigo);
            if (!$entidad) {
                Log::warning('Moneda no encontrada', [
                    'Controlador' => 'MonedaController',
                    'Metodo' => 'consultarMoneda',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Unidad de Medida no encontrada'], 404);
            }
            Log::info('Consultar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'consultarMoneda',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'consultarMoneda',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMoneda(Request $request)
    {
        try {
            $entidad = Moneda::find($request->Codigo);
            $entidad->update($request->all());
            Log::info('Actualizar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'actualizarMoneda',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Unidad de Medida actualizada correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Moneda', [
                'Controlador' => 'MonedaController',
                'Metodo' => 'actualizarMoneda',
                'codigo' => $request->Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
