<?php

namespace App\Http\Controllers\API\MotivoAnulacionVenta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoAnulacionVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MotivoAnulacionVentaController extends Controller
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

    public function listarMotivoAnulacionVenta()
    {
        try {
            $motivoAnulacion = MotivoAnulacionVenta::all();
            Log::info('Listar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'listarMotivoAnulacionVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivoAnulacion, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'listarMotivoAnulacionVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMotivoAnulacionVenta(Request $request)
    {
        try {
            MotivoAnulacionVenta::create($request->all());
            Log::info('Registrar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'registrarMotivoAnulacionVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo de Anulación de Venta registrado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'registrarMotivoAnulacionVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivoAnulacionVenta($codigo)
    {
        try {
            $motivoAnulacion = MotivoAnulacionVenta::find($codigo);
            if (!$motivoAnulacion) {
                Log::warning('Motivo de Anulación de Venta no encontrado', [
                    'Controlador' => 'MotivoAnulacionVentaController',
                    'Metodo' => 'consultarMotivoAnulacionVenta',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Motivo de Anulación de Venta no encontrado'], 404);
            }
            Log::info('Consultar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'consultarMotivoAnulacionVenta',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivoAnulacion, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'consultarMotivoAnulacionVenta',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivoAnulacionVenta(Request $request)
    {
        try {
            $motivoAnulacion = MotivoAnulacionVenta::find($request->Codigo);
            $motivoAnulacion->update($request->all());
            Log::info('Actualizar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'actualizarMotivoAnulacionVenta',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo de Anulación de Venta actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Motivo Anulacion Venta', [
                'Controlador' => 'MotivoAnulacionVentaController',
                'Metodo' => 'actualizarMotivoAnulacionVenta',
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
