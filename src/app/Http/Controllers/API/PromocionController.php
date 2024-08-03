<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promocion\ActualizacionRequest;
use App\Http\Requests\Promocion\RegistroRequest;
use App\Http\Resources\PromocionResource;
use App\Models\Promocion;
use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;

class PromocionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    private function getUploadConfig($request)
    {
        $file = $request->file('imagen');

        return [
            'file' => $file,
            'projectId' => 'sitio-web-419317',
            'bucketName' => 'gestar-peru',
            'credentialsPath' => base_path('credentials.json')
        ];
    }

    // Método para subir el archivo a Google Cloud Storage
    private function uploadFile($config)
    {
        $storage = new StorageClient([
            'projectId' => $config['projectId'],
            'keyFilePath' => $config['credentialsPath']
        ]);

        $bucket = $storage->bucket($config['bucketName']);

        $remoteFileName = 'PromocionesP/' . uniqid() . '.' . $config['file']->getClientOriginalExtension();

        $bucket->upload(fopen($config['file']->path(), 'r'), [
            'name' => $remoteFileName
        ]);

        return $remoteFileName;
    }

    //Metdo para consultar un archivo del bucket en Google Cloud Storage
    private function fileExists($fileName)
    {
        $storage = new StorageClient([
            'projectId' => 'sitio-web-419317',
            'keyFilePath' => base_path('credentials.json')
        ]);

        $bucket = $storage->bucket('gestar-peru');
        $object = $bucket->object($fileName);

        return $object->exists();
    }

    // Método para eliminar un archivo de Cloud Storage
    private function deleteFile($fileName)
    {
        $storage = new StorageClient([
            'projectId' => 'sitio-web-419317',
            'keyFilePath' => base_path('credentials.json')
        ]);

        $bucket = $storage->bucket('gestar-peru');

        // Eliminar el archivo del bucket en Cloud Storage
        $object = $bucket->object($fileName);
        $object->delete();
    }


    public function index()
    {
        try {
            Promocion
                ::where('vigente', true)
                ->whereDate('fecha_fin', '<', now())
                ->update(['vigente' => false]);

            $promociones = Promocion::all(['id', 'titulo', 'imagen', 'vigente']);

            $promocionesArray = [];

            foreach ($promociones as $promocion) {
                $promocionesArray[] = [
                    'id' => $promocion->id,
                    'titulo' => $promocion->titulo,
                    'imagen' => $promocion->imagen,
                    'vigente' => $promocion->vigente
                ];
            }
            return $promocionesArray;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las promociones' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegistroRequest $request)
    {
        try {
            // Cargar la imagen
            $uploadConfig = $this->getUploadConfig($request);
            $urlImg = $this->uploadFile($uploadConfig);

            // Cargar el archivo PDF (si se proporciona)
            $urlFile = null;
            if ($request->hasFile('file')) {
                $uploadConfig['file'] = $request->file('file');
                $urlFile = $this->uploadFile($uploadConfig);
            }

            // Crear una nueva instancia de Promocion
            $promocion = new Promocion();

            // Asignar los datos de la solicitud al modelo Promocion
            $promocion->titulo = $request->input('titulo');
            $promocion->fecha_inicio = $request->input('fecha_inicio');
            $promocion->fecha_fin = $request->input('fecha_fin');
            $promocion->descripcion = $request->input('descripcion');
            $promocion->sedes = $request->input('sedes');
            $promocion->imagen = $urlImg; // URL de la imagen
            $promocion->file = $urlFile; // URL del archivo PDF

            // Guardar el modelo Promocion en la base de datos
            $promocion->save();

            // Devolver una respuesta JSON indicando que la promoción se ha registrado correctamente
            return response()->json([
                'message' => 'Promoción registrada correctamente'
            ], 201);
        } catch (\Exception $e) {
            // Devolver una respuesta JSON indicando que ocurrió un error al intentar registrar la promoción
            return response()->json([
                'error' => 'Error al registrar la promoción' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Promocion $promocion)
    {
        return new PromocionResource($promocion);
    }

    public function updatePost(ActualizacionRequest $request)
    {
        try {

            $promocion = Promocion::find($request->input('id'));

            if (!$promocion) {
                return response()->json([
                    'error' => 'Promoción no encontrada'
                ], 404);
            }


            if ($request->hasFile('imagen')) {
                $uploadConfig = $this->getUploadConfig($request);
                $url = $this->uploadFile($uploadConfig);

                if ($promocion->imagen != null && $this->fileExists($promocion->imagen)) {
                    $this->deleteFile($promocion->imagen);
                }

                $promocion->imagen = $url;
            }


            $urlFile = null;
            if ($request->hasFile('file')) {
                $uploadConfig['file'] = $request->file('file');
                $urlFile = $this->uploadFile($uploadConfig);

                if ($promocion->file != null && $this->fileExists($promocion->file)) {
                    $this->deleteFile($promocion->file);
                }

                $promocion->file = $urlFile;
            }

            $promocion->titulo = $request->input('titulo');
            $promocion->fecha_inicio = $request->input('fecha_inicio');
            $promocion->fecha_fin = $request->input('fecha_fin');
            $promocion->descripcion = $request->input('descripcion');
            $promocion->sedes = $request->input('sedes');
            $promocion->vigente = $request->input('vigente');

            $promocion->save();

            return response()->json([
                'message' => 'Promoción actualizada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar la promoción' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promocion $promocion)
    {
        try {

            if ($promocion->imagen != null && $this->fileExists($promocion->imagen)) {
                $this->deleteFile($promocion->imagen);
            }

            if ($promocion->file != null && $this->fileExists($promocion->file)) {
                $this->deleteFile($promocion->file);
            }

            $promocion->delete();

            return response()->json([
                'message' => 'Promoción eliminada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar la promoción' . $e->getMessage()
            ], 500);
        }
    }

    public function listarVigentes()
    {
        try {
            // Actualizar el estado de las promociones cuya fecha de finalización ha pasado
            // Promocion
            //     ::where('vigente', true)
            //     ->whereDate('fecha_fin', '<', now()) // Cambiado de '>' a '<'
            //     ->update(['vigente' => false]);

            // Obtener las promociones vigentes después de la actualización
            $promocionesVigentes = Promocion
                ::where('vigente', true)
                ->whereDate('fecha_fin', '<', now())
                ->orderBy('id', 'asc')
                ->get();

            $promocionesArray = [];
            foreach ($promocionesVigentes as $promocion) {
                $promocionesArray[] = [
                    'id' => $promocion->id,
                    'titulo' => $promocion->titulo,
                    'imagen' => $promocion->imagen,
                    'file' => $promocion->file,
                    'fecha_inicio' => $promocion->fecha_inicio,
                    'fecha_fin' => $promocion->fecha_fin,
                ];
            }

            return $promocionesArray;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las promociones' . $e->getMessage()
            ], 500);
        }
    }

    public function consultar($id)
    {
        $promocion = Promocion::where('id', $id)
            ->where('vigente', true)
            ->first();

        if (!$promocion) {
            return response()->json([
                'error' => 'Promoción no encontrada'
            ], 404);
        }
        return new PromocionResource($promocion);
    }
}
