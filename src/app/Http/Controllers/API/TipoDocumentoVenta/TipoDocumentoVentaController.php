<?php

namespace App\Http\Controllers\API\TipoDocumentoVenta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\TipoDocumentosVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TipoDocumentoVentaController extends Controller
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

    public function registrarDocVenta(Request $request)
    {
        $documento = $request->input('documento');

        if (!empty($documento['CodigoSUNAT'])) { // Validamos que no sea null ni vacío
            if (TipoDocumentosVenta::where('CodigoSUNAT', $documento['CodigoSUNAT'])->exists()) {
                //log warning
                Log::warning('Intento de registrar un documento con Código SUNAT ya existente.', [
                    'CodigoSUNAT' => $documento['CodigoSUNAT'],
                    'Controlador' => 'TipoDocumentoVentaController',
                    'Metodo' => 'registrarDocVenta'
                ]);

                return response()->json([
                    'error' => 'El Código SUNAT ya existe.'
                ], 400); // Código de error 400 (Bad Request) en lugar de 500 (Internal Server Error)
            }
        }


        try {
            TipoDocumentosVenta::create($documento);

            //log info
            Log::info('Documento Venta registrado correctamente.', [
                'CodigoSUNAT' => $documento['CodigoSUNAT'],
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'registrarDocVenta'
            ]);

            return response()->json(['message' => 'Documento Venta registrado correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar Documento Venta.', [
                'CodigoSUNAT' => $documento['CodigoSUNAT'],
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'registrarDocVenta',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarTipoDocumentoVenta()
    {
        try {
            $documentos = TipoDocumentosVenta::all();

            //log info
            Log::info('Listado de documentos de venta consultado correctamente.', [
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'listarTipoDocumentoVenta',
                'Cantidad' => $documentos->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($documentos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar documentos de venta.', [
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'listarTipoDocumentoVenta',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarDocVenta(Request $request)
    {
        $documento = $request->input('documento');
        try {
            TipoDocumentosVenta::where('Codigo', $documento['Codigo'])->update($documento);

            //log info
            Log::info('Documento Venta actualizado correctamente.', [
                'Codigo' => $documento['Codigo'],
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'actualizarDocVenta'
            ]);

            return response()->json(['message' => 'Documento Venta actualizado correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar Documento Venta.', [
                'Codigo' => $documento['Codigo'],
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'actualizarDocVenta',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarDocVenta($codigo)
    {
        try {
            $documento = TipoDocumentosVenta::where('Codigo', $codigo)->first();

            if (!$documento) {
                //log warning
                Log::warning('Documento Venta no encontrado.', [
                    'Codigo' => $codigo,
                    'Controlador' => 'TipoDocumentoVentaController',
                    'Metodo' => 'consultarDocVenta',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'

                ]);

                return response()->json(['error' => 'Documento Venta no encontrado'], 404);
            }

            //log info
            Log::info('Documento Venta consultado correctamente.', [
                'Codigo' => $codigo,
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'consultarDocVenta',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'

            ]);

            return response()->json($documento, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Documento Venta.', [
                'Codigo' => $codigo,
                'Controlador' => 'TipoDocumentoVentaController',
                'Metodo' => 'consultarDocVenta',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
