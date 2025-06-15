<?php

namespace App\Http\Controllers\API\UnidadMedida;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnidadMedidaController extends Controller
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

    public function listarUnidadMedidad()
    {
        try {
            $entidad = UnidadMedida::all();

            // Log info
            Log::info('Listado de unidades de medida consultado correctamente.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'listarUnidadMedidad',
                'Cantidad' => $entidad->count(),
            ]);

            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            // Log error
            Log::error('Error al listar unidades de medida.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'listarUnidadMedidad',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarUnidadMedidad(Request $request)
    {
        try {
            UnidadMedida::create($request->all());

            // Log info
            Log::info('Unidad de Medida registrada correctamente.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'registrarUnidadMedidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['message' => 'Unidad de Medida registrada correctamente'], 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error al registrar unidad de medida.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'registrarUnidadMedidad',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarUnidadMedidad($codigo)
    {
        try {
            $entidad = UnidadMedida::find($codigo);

            if (!$entidad) {
                //log warning
                Log::warning('Unidad de Medida no encontrada.', [
                    'Controlador' => 'UnidadMedidaController',
                    'Metodo' => 'consultarUnidadMedidad',
                    'codigo' => $codigo,
                ]);
                return response()->json(['error' => 'Unidad de Medida no encontrada'], 404);
            }

            // Log info
            Log::info('Unidad de Medida consultada correctamente.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'consultarUnidadMedidad',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            // Log error
            Log::error('Error al consultar unidad de medida.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'consultarUnidadMedidad',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarUnidadMedidad(Request $request)
    {
        try {
            $entidad = UnidadMedida::find($request->Codigo);
            $entidad->update($request->all());

            // Log info
            Log::info('Unidad de Medida actualizada correctamente.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'actualizarUnidadMedidad',
                'Codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['message' => 'Unidad de Medida actualizada correctamente'], 200);
        } catch (\Exception $e) {

            // Log error
            Log::error('Error al actualizar unidad de medida.', [
                'Controlador' => 'UnidadMedidaController',
                'Metodo' => 'actualizarUnidadMedidad',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'comando' => $request->all(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
