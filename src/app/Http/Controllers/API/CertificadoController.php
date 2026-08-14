<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DetalleCertificado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CertificadoController extends Controller
{
    /**
     * Listar certificados
     */
    public function index(): JsonResponse
    {
        $certificados = DetalleCertificado::with('medico')->get();

        return response()->json($certificados, 200);
    }


    public function listarCertificadosMedico($medico): JsonResponse
    {
        $certificados = DetalleCertificado::where('CodigoMedico', $medico)->get();

        return response()->json($certificados, 200);
    }


    /**
     * Mostrar un certificado por id
     */
    public function show($id): JsonResponse
    {
        $certificado = DetalleCertificado::find($id);

        if (!$certificado) {
            return response()->json([
                'message' => 'Certificado no encontrado'
            ], 404);
        }

        return response()->json($certificado, 200);
    }

    /**
     * Crear un certificado
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'CodigoMedico' => 'required|integer|min:1',
            'Nombre' => 'required|string|max:150',
            'FechaEmision' => 'required|date',
            'FechaCaducidad' => 'nullable|date|after_or_equal:FechaEmision',
            'Logo' => 'nullable|string|max:255',
            'Descripcion' => 'nullable|string|max:255',
            'Vigente' => 'nullable|boolean',
        ]);

        $certificado = DetalleCertificado::create([
            'CodigoMedico' => $request->CodigoMedico,
            'Nombre' => $request->Nombre,
            'FechaEmision' => $request->FechaEmision,
            'FechaCaducidad' => $request->FechaCaducidad,
            'Logo' => $request->Logo,
            'Descripcion' => $request->Descripcion,
            'Vigente' => $request->Vigente ?? true,
        ]);

        return response()->json([
            'message' => 'Certificado registrado correctamente',
            'data' => $certificado,
        ], 201);
    }

    /**
     * Actualizar un certificado
     */
    public function update(Request $request, $id): JsonResponse
    {
        $certificado = DetalleCertificado::find($id);

        if (!$certificado) {
            return response()->json([
                'message' => 'Certificado no encontrado'
            ], 404);
        }

        $request->validate([
            'Nombre' => 'sometimes|required|string|max:150',
            'FechaEmision' => 'sometimes|required|date',
            'FechaCaducidad' => 'nullable|date|after_or_equal:FechaEmision',
            'Logo' => 'nullable|string|max:255',
            'Descripcion' => 'nullable|string|max:255',
            'Vigente' => 'nullable|boolean',
        ]);

        $certificado->fill($request->all());
        $certificado->save();

        return response()->json([
            'message' => 'Certificado actualizado correctamente',
            'data' => $certificado,
        ], 200);
    }

    /**
     * Eliminar un certificado
     */
    public function destroy($id): JsonResponse
    {
        $certificado = DetalleCertificado::find($id);

        if (!$certificado) {
            return response()->json([
                'message' => 'Certificado no encontrado'
            ], 404);
        }

        $certificado->delete();

        return response()->json([
            'message' => 'Certificado eliminado correctamente'
        ], 200);
    }
}
