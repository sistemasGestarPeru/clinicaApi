<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TipoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

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

    public function registrarTipoDocumento(Request $request)
    {
        $tipoDocumento = $request->input('tipoDocumento');

        try {

            TipoDocumento::create($tipoDocumento);
            //log info
            Log::info('Registrar Tipo Documento', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'registrarTipoDocumento',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'tipo_documento' => $tipoDocumento
            ]);
            return response()->json(['message' => 'Tipo Documento registrado correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar Tipo Documento', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'registrarTipoDocumento',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarTipoDocumento(Request $request)
    {
        $tipoDocumento = $request->input('tipoDocumento');

        try {
            TipoDocumento::where('Codigo', $tipoDocumento['Codigo'])->update($tipoDocumento);
            //log info
            Log::info('Actualizar Tipo Documento', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'actualizarTipoDocumento',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'tipo_documento' => $tipoDocumento
            ]);
            return response()->json(['message' => 'Tipo Documento actualizado correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar Tipo Documento', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'actualizarTipoDocumento',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'tipo_documento' => $tipoDocumento
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarTipoDocumentos()
    {
        try {
            $tipoDocumento = TipoDocumento::all();

            //log info
            Log::info('Listar Tipo Documentos', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'listarTipoDocumentos',
                'Cantidad' => $tipoDocumento->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($tipoDocumento);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar Tipo Documentos', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'listarTipoDocumentos',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTipoDocumento($codigo)
    {
        try {
            $tipoDocumento = TipoDocumento::find($codigo);

            //log info
            Log::info('Consultar Tipo Documento', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'consultarTipoDocumento',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($tipoDocumento);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Tipo Documento', [
                'Controlador' => 'TipoDocumentoController',
                'Metodo' => 'consultarTipoDocumento',
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
