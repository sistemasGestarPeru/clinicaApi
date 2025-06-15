<?php

namespace App\Http\Controllers\API\MotivoNotaCredito;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\MotivoNotaCredito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MotivoNotaCreditoController extends Controller
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


    public function registrarMotivos(Request $request)
    {

        $motivo = $request->input('motivo');
        try {
            MotivoNotaCredito::create($motivo);
            Log::info('Registrar Motivo de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'registrarMotivos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo de Nota de Crédito registrado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar Motivo de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'registrarMotivos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarMotivos()
    {
        try {
            $motivos = MotivoNotaCredito::all();
            Log::info('Listar Motivos de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'listarMotivos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivos, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Motivos de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'listarMotivos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMotivo(Request $request)
    {
        $motivo = $request->input('motivo');
        try {
            MotivoNotaCredito::where('Codigo', $motivo['Codigo'])->update($motivo);
            Log::info('Actualizar Motivo de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'actualizarMotivo',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Motivo de Nota de Crédito actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Motivo de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'actualizarMotivo',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMotivo($codigo)
    {
        try {
            $motivo = MotivoNotaCredito::where('Codigo', $codigo)->first();
            if (!$motivo) {
                Log::warning('Motivo de Nota de Crédito no encontrado', [
                    'Controlador' => 'MotivoNotaCreditoController',
                    'Metodo' => 'consultarMotivo',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Motivo de Nota de Crédito no encontrado'], 404);
            }
            Log::info('Consultar Motivo de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'consultarMotivo',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivo, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Motivo de Nota de Crédito', [
                'Controlador' => 'MotivoNotaCreditoController',
                'Metodo' => 'consultarMotivo',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
