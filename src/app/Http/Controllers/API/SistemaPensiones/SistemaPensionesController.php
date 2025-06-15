<?php

namespace App\Http\Controllers\API\SistemaPensiones;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\SistemaPension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SistemaPensionesController extends Controller
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

    public function listarSistemaPensiones()
    {
        try {
            $sistemaPensiones = SistemaPension::all();
            //log info
            Log::info('Listado de sistemas de pensiones consultado correctamente.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'listarSistemaPensiones',
                'Cantidad' => $sistemaPensiones->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'

            ]);
            return response()->json($sistemaPensiones, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar sistemas de pensiones.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'listarSistemaPensiones',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarSistemaPensiones(Request $request)
    {
        try {
            SistemaPension::create($request->all());
            //log info
            Log::info('Sistema de Pensión registrado correctamente.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'registrarSistemaPensiones',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Sistema de Pensión registrado correctamente.', 201);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar sistema de pensiones.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'registrarSistemaPensiones',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarSistemaPensiones(Request $request)
    {
        try {
            $sistemaPensiones = SistemaPension::find($request->Codigo);
            $sistemaPensiones->update($request->all());

            //log info
            Log::info('Sistema de Pensión actualizado correctamente.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'actualizarSistemaPensiones',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'comando' => $request->all()
            ]);

            return response()->json('Sistema de Pensión actualizado correctamente.', 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar sistema de pensiones.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'actualizarSistemaPensiones',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'comando' => $request->all()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarSistemaPensiones($codigo)
    {
        try {
            $sistemaPensiones = SistemaPension::find($codigo);

            if (!$sistemaPensiones) {
                //log warning
                Log::warning('Sistema de pensión no encontrado.', [
                    'Controlador' => 'SistemaPensionesController',
                    'Metodo' => 'consultarSistemaPensiones',
                    'codigo_sistema_pension' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Sistema de pensión no encontrado'], 404);
            }

            //log info
            Log::info('Sistema de pensión consultado correctamente.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'consultarSistemaPensiones',
                'codigo_sistema_pension' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($sistemaPensiones, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar sistema de pensiones.', [
                'Controlador' => 'SistemaPensionesController',
                'Metodo' => 'consultarSistemaPensiones',
                'codigo_sistema_pension' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
