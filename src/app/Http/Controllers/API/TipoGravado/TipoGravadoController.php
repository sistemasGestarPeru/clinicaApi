<?php

namespace App\Http\Controllers\API\TipoGravado;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\TipoGravado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TipoGravadoController extends Controller
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


    public function listarTipoGravado()
    {
        try {
            $entidad = TipoGravado::all();

            //log info
            Log::info('Listado de tipos de gravado consultado correctamente.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'listarTipoGravado',
                'Cantidad' => $entidad->count(),
            ]);

            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar tipos de gravado.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'listarTipoGravado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',

            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarTipoGravado(Request $request)
    {
        try {
            TipoGravado::create($request->all());

            //log info
            Log::info('Tigo de Gravado registrado correctamente.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'registrarTipoGravado',
                'Codigo' => $request->Codigo,
            ]);

            return response()->json(['message' => 'Tigo de Gravado registrado correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar tipo de gravado.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'registrarTipoGravado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTipoGravado($codigo)
    {
        try {
            $entidad = TipoGravado::find($codigo);

            if (!$entidad) {
                //log warning
                Log::warning('Tipo de Gravado no encontrado.', [
                    'Controlador' => 'TipoGravadoController',
                    'Metodo' => 'consultarTipoGravado',
                    'Codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'Tipo de Gravado no encontrado'], 404);
            }

            //log info
            Log::info('Tipo de Gravado consultado correctamente.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'consultarTipoGravado',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar tipo de gravado.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'consultarTipoGravado',
                'Codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarTipoGravado(Request $request)
    {
        try {
            $entidad = TipoGravado::find($request->Codigo);

            if (!$entidad) {
                //log warning
                Log::warning('Tipo de Gravado no encontrado para actualización.', [
                    'Controlador' => 'TipoGravadoController',
                    'Metodo' => 'actualizarTipoGravado',
                    'Codigo' => $request->Codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'Tipo de Gravado no encontrado'], 404);
            }

            //log info
            Log::info('Actualización de Tipo de Gravado iniciada.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'actualizarTipoGravado',
                'Codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            $entidad->update($request->all());

            //log info
            Log::info('Tipo de Gravado actualizado correctamente.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'actualizarTipoGravado',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['message' => 'Tigo de Gravado actualizado correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar tipo de gravado.', [
                'Controlador' => 'TipoGravadoController',
                'Metodo' => 'actualizarTipoGravado',
                'Codigo' => $request->Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'comando' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
