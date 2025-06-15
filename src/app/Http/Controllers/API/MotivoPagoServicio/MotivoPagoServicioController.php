<?php

namespace App\Http\Controllers\API\MotivoPagoServicio;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoPagoServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MotivoPagoServicioController extends Controller
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


    public function listarMotivoPagoServicios()
    {
        try {
            $motivoPagoServicio = MotivoPagoServicio::all();
            Log::info('Listar Motivo Pago de Servicios', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'listarMotivoPagoServicios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivoPagoServicio, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Motivo Pago de Servicios', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'listarMotivoPagoServicios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMotivoPagoServicios(Request $request)
    {
        try {
            MotivoPagoServicio::create($request->all());
            Log::info('Registrar Motivo Pago de Servicio', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'registrarMotivoPagoServicios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo Pago de Servicio registrado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar Motivo Pago de Servicio', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'registrarMotivoPagoServicios',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivoPagoServicios($codigo)
    {
        try {
            $motivoPagoServicio = MotivoPagoServicio::find($codigo);
            if (!$motivoPagoServicio) {
                Log::warning('Motivo Pago de Servicio no encontrado', [
                    'Controlador' => 'MotivoPagoServicioController',
                    'Metodo' => 'consultarMotivoPagoServicios',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Motivo Pago de Servicio no encontrado'], 404);
            }
            return response()->json($motivoPagoServicio, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Motivo Pago de Servicio', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'consultarMotivoPagoServicios',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivoPagoServicios(Request $request)
    {
        try {
            $motivoPagoServicio = MotivoPagoServicio::find($request->Codigo);
            $motivoPagoServicio->update($request->all());
            Log::info('Actualizar Motivo Pago de Servicio', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'actualizarMotivoPagoServicios',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo Pago de Servicio actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Motivo Pago de Servicio', [
                'Controlador' => 'MotivoPagoServicioController',
                'Metodo' => 'actualizarMotivoPagoServicios',
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
