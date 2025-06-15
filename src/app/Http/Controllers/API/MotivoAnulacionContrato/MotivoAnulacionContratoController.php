<?php

namespace App\Http\Controllers\API\MotivoAnulacionContrato;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoAnulacionContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MotivoAnulacionContratoController extends Controller
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

    public function listarMotivoAnulacionContrato()
    {
        try {
            $motivoAnulacionContrato = MotivoAnulacionContrato::all();
            Log::info('Listar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'listarMotivoAnulacionContrato',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivoAnulacionContrato, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'listarMotivoAnulacionContrato',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMotivoAnulacionContrato(Request $request)
    {
        try {
            MotivoAnulacionContrato::create($request->all());
            Log::info('Registrar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'registrarMotivoAnulacionContrato',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo de anulación de contrato registrado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'registrarMotivoAnulacionContrato',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivoAnulacionContrato($codigo)
    {
        try {
            $motivoAnulacionContrato = MotivoAnulacionContrato::find($codigo);
            if (!$motivoAnulacionContrato) {
                Log::warning('Motivo de anulación de contrato no encontrado', [
                    'Controlador' => 'MotivoAnulacionContratoController',
                    'Metodo' => 'consultarMotivoAnulacionContrato',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Motivo de anulación de contrato no encontrado'], 404);
            }
            Log::info('Consultar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'consultarMotivoAnulacionContrato',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivoAnulacionContrato, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'consultarMotivoAnulacionContrato',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivoAnulacionContrato(Request $request)
    {
        try {
            $motivoAnulacionContrato = MotivoAnulacionContrato::find($request->Codigo);
            $motivoAnulacionContrato->update($request->all());
            Log::info('Actualizar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'actualizarMotivoAnulacionContrato',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo de anulación de contrato actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Motivo Anulacion Contrato', [
                'Controlador' => 'MotivoAnulacionContratoController',
                'Metodo' => 'actualizarMotivoAnulacionContrato',
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
