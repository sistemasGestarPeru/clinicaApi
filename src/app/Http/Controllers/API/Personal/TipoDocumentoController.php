<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

            return response()->json(['message' => 'Tipo Documento registrado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarTipoDocumento(Request $request, $codigo)
    {
        $tipoDocumento = $request->input('tipoDocumento');

        try {
            $tipoDocumento = TipoDocumento::find($codigo);
            $tipoDocumento->update($tipoDocumento);

            return response()->json(['message' => 'Tipo Documento actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarTipoDocumentos()
    {
        try {
            $tipoDocumento = TipoDocumento::all();
            return response()->json($tipoDocumento);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTipoDocumento($codigo)
    {
        try {
            $tipoDocumento = TipoDocumento::find($codigo);
            return response()->json($tipoDocumento);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
