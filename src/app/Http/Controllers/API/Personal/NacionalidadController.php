<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\Nacionalidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NacionalidadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Nacionalidad::all();
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

    public function listarNacionalidad()
    {
        try {
            $nacionalidad = Nacionalidad::all();
            //log info
            Log::info('Listar Nacionalidades', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'listarNacionalidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $nacionalidad->count()
            ]);
            return response()->json($nacionalidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar nacionalidades', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'listarNacionalidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarNacionalidad(Request $request)
    {
        try {
            Nacionalidad::create($request->all());
            Log::info('Registrar Nacionalidad', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'registrarNacionalidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Nacionalidad registrada correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar nacionalidad', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'registrarNacionalidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'command' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarNacionalidad($codigo)
    {
        try {
            $nacionalidad = Nacionalidad::find($codigo);
            // log info
            Log::info('Consultar Nacionalidad', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'consultarNacionalidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $codigo
            ]);
            return response()->json($nacionalidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar nacionalidad', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'consultarNacionalidad',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarNacionalidad(Request $request)
    {
        try {
            $nacionalidad = Nacionalidad::find($request->Codigo);
            $nacionalidad->update($request->all());
            Log::info('Actualizar Nacionalidad', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'actualizarNacionalidad',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Nacionalidad actualizada correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar nacionalidad', [
                'Controlador' => 'NacionalidadController',
                'Metodo' => 'actualizarNacionalidad',
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
