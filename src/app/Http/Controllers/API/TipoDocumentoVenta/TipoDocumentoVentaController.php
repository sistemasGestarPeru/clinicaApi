<?php

namespace App\Http\Controllers\API\TipoDocumentoVenta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\TipoDocumentosVenta;
use Illuminate\Http\Request;

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
                return response()->json([
                    'error' => 'El Código SUNAT ya existe.'
                ], 400); // Código de error 400 (Bad Request) en lugar de 500 (Internal Server Error)
            }
        }
        

        try {
            TipoDocumentosVenta::create($documento);
            return response()->json(['message' => 'Documento Venta registrado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarTipoDocumentoVenta()
    {
        try {
            $documentos = TipoDocumentosVenta::all();
            return response()->json($documentos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarDocVenta(Request $request)
    {
        $documento = $request->input('documento');
        try {
            TipoDocumentosVenta::where('Codigo', $documento['Codigo'])->update($documento);
            return response()->json(['message' => 'Documento Venta actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarDocVenta($codigo)
    {
        try {
            $documento = TipoDocumentosVenta::where('Codigo', $codigo)->first();
            return response()->json($documento, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
