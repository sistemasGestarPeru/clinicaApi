<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portada\ActualizarRequest;
use App\Http\Requests\Portada\RegistroRequest;
use App\Http\Resources\PortadaResource;
use App\Models\Portada;
use Illuminate\Http\Request;
use Google\Cloud\Storage\StorageClient;

class PortadaController extends Controller
{
    private function getUploadConfig($request, $fieldName)
    {
        $file = $request->file($fieldName);

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

        $remoteFileName = 'Portadas/' . uniqid() . '.' . $config['file']->getClientOriginalExtension();

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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $portada = Portada::all('id', 'imagenEsc', 'vigente');

            $portadasArray = [];

            foreach ($portada as $item) {
                $portadasArray[] = [
                    'id' => $item->id,
                    'imagenEsc' => $item->imagenEsc,
                    'vigente' => $item->vigente
                ];
            }

            return $portadasArray;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las portadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegistroRequest $request)
    {
        try {
            // Sube la imagen de la portada - Escritorio
            $urlImgE = null;
            if ($request->hasFile('imagen')) {
                $uploadConfigEsc = $this->getUploadConfig($request, 'imagen');
                $urlImgE = $this->uploadFile($uploadConfigEsc);
            }

            // Sube la imagen de la portada - Celular
            $urlCel = null;
            if ($request->hasFile('imagenCel')) {
                $uploadConfigCel = $this->getUploadConfig($request, 'imagenCel');
                $urlCel = $this->uploadFile($uploadConfigCel);
            }

            $portada = new Portada();

            $portada->imagenEsc = $urlImgE;
            $portada->imagenCel = $urlCel;
            $portada->TextoBtn = $request->input('TextoBtn');
            $portada->UrlBtn = $request->input('UrlBtn');

            $portada->save();

            return response()->json([
                'message' => 'Portada registrada correctamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar la portada',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(Portada $portada)
    {
        return new PortadaResource($portada);
    }

    /**
     * Update con Post the specified resource in storage.
     */

    public function updatePost(ActualizarRequest $request)
    {
        try {
            $portada = Portada::find($request->input('id'));

            if (!$portada) {
                return response()->json([
                    'error' => 'Portada no encontrada'
                ], 404);
            }

            // Subir y actualizar la imagen de la portada - Escritorio
            if ($request->hasFile('imagen')) {
                $uploadConfigEsc = $this->getUploadConfig($request, 'imagen');
                $urlImgE = $this->uploadFile($uploadConfigEsc);

                // Eliminar la imagen anterior si existe
                if ($portada->imagenEsc != null && $this->fileExists($portada->imagenEsc)) {
                    $this->deleteFile($portada->imagenEsc);
                }

                // Asignar la nueva URL de la imagen
                $portada->imagenEsc = $urlImgE;
            }

            // Subir y actualizar la imagen de la portada - Celular
            if ($request->hasFile('imagenCel')) {
                $uploadConfigCel = $this->getUploadConfig($request, 'imagenCel');
                $urlCel = $this->uploadFile($uploadConfigCel);

                // Eliminar la imagen anterior si existe
                if ($portada->imagenCel != null && $this->fileExists($portada->imagenCel)) {
                    $this->deleteFile($portada->imagenCel);
                }

                // Asignar la nueva URL de la imagen
                $portada->imagenCel = $urlCel;
            }

            // Actualizar el texto del botón y la URL del botón
            $portada->TextoBtn = $request->input('TextoBtn');
            $portada->UrlBtn = $request->input('UrlBtn');

            // Guardar los cambios en la base de datos
            $portada->save();

            return response()->json([
                'message' => 'Portada actualizada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la portada',
                'error' => $e->getMessage()
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
    public function destroy(Portada $portada)
    {
        try {

            if ($portada->imagenEsc != null && $this->fileExists($portada->imagenEsc)) {
                $this->deleteFile($portada->imagenEsc);
            }

            if ($portada->imagenCel != null && $this->fileExists($portada->imagenCel)) {
                $this->deleteFile($portada->imagenCel);
            }

            $portada->delete();

            return response()->json([
                'message' => 'Portada eliminada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la portada',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarVigentes()
    {
        try {

            $portadaVigente = Portada
                ::where('vigente', true)
                ->orderBy('id', 'asc')
                ->get();

            $portadasArray = [];

            foreach ($portadaVigente as $item) {
                $portadasArray[] = [
                    'id' => $item->id,
                    'imagen' => $item->imagenEsc,
                    'TextoBtn' => $item->TextoBtn,
                    'UrlBtn' => $item->UrlBtn
                ];
            }

            return $portadasArray;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las portadas vigentes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultar($id)
    {
        $portada = Portada::where('id', $id)
            ->where('vigente', true)
            ->first();

        if (!$portada) {
            return response()->json([
                'message' => 'Portada no encontrada'
            ], 404);
        }

        return new PortadaResource($portada);
    }
}
