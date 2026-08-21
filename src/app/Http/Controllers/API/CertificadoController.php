<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DetalleCertificado;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CertificadoController extends Controller
{
    private function uploadFile(Request $request, string $fieldName = 'archivo'): string
    {
        $storage = new StorageClient([
            'projectId' => 'sitio-web-419317',
            'keyFilePath' => base_path('credentials.json'),
        ]);

        $bucket = $storage->bucket('gestar-peru');
        $file = $request->file($fieldName);
        $remoteFileName = 'Certificados/' . uniqid() . '.' . $file->getClientOriginalExtension();

        $bucket->upload(fopen($file->path(), 'r'), [
            'name' => $remoteFileName,
        ]);

        return $remoteFileName;
    }

    private function deleteFile(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $storage = new StorageClient([
            'projectId' => 'sitio-web-419317',
            'keyFilePath' => base_path('credentials.json'),
        ]);

        $object = $storage->bucket('gestar-peru')->object($fileName);
        if ($object->exists()) {
            $object->delete();
        }
    }

    private function uploadedFileField(Request $request): ?string
    {
        if ($request->hasFile('archivo')) {
            return 'archivo';
        }

        return $request->hasFile('Logo') ? 'Logo' : null;
    }

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
            'Codigo' => 'nullable|integer|min:0',
            'CodigoMedico' => 'required|integer|min:1',
            'Nombre' => 'required|string|max:150',
            'institucion' => 'nullable|string|max:150',
            'FechaEmision' => 'required|date',
            'FechaCaducidad' => 'nullable|date|after_or_equal:FechaEmision',
            'Descripcion' => 'nullable|string|max:255',
            'Vigente' => 'nullable|boolean',
            'archivo' => 'required_without:Logo|file|max:10240',
            'Logo' => 'required_without:archivo|file|max:10240',
            'Destacado'=> 'nullable',
        ]);

        $fileField = $this->uploadedFileField($request);
        $archivo = $this->uploadFile($request, $fileField);

        $certificado = DetalleCertificado::create([
            'CodigoMedico' => $request->CodigoMedico,
            'Nombre' => $request->Nombre,
            'Institucion' => $request->input('institucion', $request->input('Institucion')),
            'FechaEmision' => $request->FechaEmision,
            'FechaCaducidad' => $request->FechaCaducidad,
            'Logo' => $archivo,
            'Descripcion' => $request->Descripcion,
            'Vigente' => $request->Vigente ?? true,
            'Destacado' => $request->boolean('Destacado') ? 1 : 0
        ]);

        return response()->json([
            'message' => 'Certificado registrado correctamente',
            'data' => $certificado,
        ], 201);
    }

    /**
     * Actualizar un certificado
     */
    public function update(Request $request): JsonResponse
    {
        $certificado = DetalleCertificado::find($request->Codigo);

        if (!$certificado) {
            return response()->json([
                'message' => 'Certificado no encontrado'
            ], 404);
        }

        $request->validate([
            'Nombre' => 'sometimes|required|string|max:150',
            'institucion' => 'sometimes|nullable|string|max:150',
            'FechaEmision' => 'sometimes|required|date',
            'FechaCaducidad' => 'nullable|date|after_or_equal:FechaEmision',
            'Logo' => $request->hasFile('Logo') ? 'file|max:10240' : 'nullable|string|max:255',
            'Descripcion' => 'nullable|string|max:255',
            'Vigente' => 'nullable|boolean',
            'archivo' => 'sometimes|required|file|max:10240',
            'Destacado'=> 'nullable',
        ]);

        $data = $request->only([
            'Nombre',
            'FechaEmision',
            'FechaCaducidad',
            'Logo',
            'Descripcion',
            'Vigente',
            'Destacado'
        ]);

        if ($request->has('Destacado')) {
            $data['Destacado'] = $request->boolean('Destacado') ? 1 : 0;
        }

        if ($request->has('institucion')) {
            $data['Institucion'] = $request->input('institucion');
        } elseif ($request->has('Institucion')) {
            $data['Institucion'] = $request->input('Institucion');
        }

        $fileField = $this->uploadedFileField($request);
        $hasNewFile = $fileField !== null;
        if ($hasNewFile) {
            $data['Logo'] = $this->uploadFile($request, $fileField);
        }

        $oldLogo = $certificado->Logo;
        $certificado->fill($data);
        $certificado->save();

        if ($hasNewFile) {
            $this->deleteFile($oldLogo);
        }

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

        $this->deleteFile($certificado->Logo);
        $certificado->delete();

        return response()->json([
            'message' => 'Certificado eliminado correctamente'
        ], 200);
    }
}
