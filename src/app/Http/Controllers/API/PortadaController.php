<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portada\ActualizarRequest;
use App\Http\Requests\Portada\RegistroRequest;
use App\Http\Resources\PortadaResource;
use App\Models\Portada;
use App\Models\Sede;
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
            $portadas = Portada::select('imagenEsc', 'identificadorPadre', 'TextoBtn', 'id')
                ->where('identificadorHijo', 0)
                ->get();
            return response()->json($portadas);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las portadas',
                'error' => $e->getMessage()
            ], 500);
        }

        // try {
        //     $portada = Portada::where('identificadorHijo', 1)
        //         ->get(['imagenEsc', 'identificadorPadre', 'TextoBtn']);

        //     $portadasArray = [];

        //     foreach ($portada as $item) {
        //         $portadasArray[] = [
        //             'imagenEsc' => $item->imagenEsc,
        //             'identificadorPadre' => $item->identificadorPadre,
        //             'TextoBtn' => $item->TextoBtn
        //         ];
        //     }

        //     return $portadasArray;
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'message' => 'Error al obtener las portadas',
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }

    public function consultarListado($id)
    {
        try {
            $portadas = Portada::select('id', 'imagenEsc')
                ->where('identificadorPadre', $id)
                ->where('identificadorHijo', '!=', 0)
                ->get();
            return response()->json($portadas);

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

            $textoBtn = $request->input('TextoBtn');
            $UrlBtn = $request->input('UrlBtn');

            $portada->UrlBtn = ($UrlBtn === 'undefined' || $UrlBtn === 'null') ? null : $UrlBtn;
            $portada->TextoBtn = ($textoBtn === 'undefined' || $textoBtn === 'null') ? null : $textoBtn;

            $portada->identificadorPadre = $request->input('identificadorPadre');
            $portada->identificadorHijo = $request->input('identificadorHijo');
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


    public function updateTitulos(Request $request){

        try{
            $portada = Portada::find($request->input('id'));
            if (!$portada) {
                return response()->json([
                    'error' => 'Portada no encontrada'
                ], 404);
            }
            $portada->Titulo = $request->input('Titulo');
            $portada->Descripcion = $request->input('Descripcion');
            $portada->save();
            
            return response()->json([
                'message' => 'Portada actualizada correctamente'
            ], 200);

        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la portada',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updatePost(ActualizarRequest $request)
    {
        try {
            $portada = Portada::find($request->input('id'));

            if (!$portada) {
                return response()->json([
                    'error' => 'Portada no encontrada'
                ], 404);
            }

            // Actualizar el texto del botón y la URL del botón

            $textoBtn = $request->input('TextoBtn');
            $UrlBtn = $request->input('UrlBtn');

            $portada->UrlBtn = ($UrlBtn === 'undefined' || $UrlBtn === 'null') ? null : $UrlBtn;
            $portada->TextoBtn = ($textoBtn === 'undefined' || $textoBtn === 'null') ? null : $textoBtn;

            $portada->vigente = $request->input('vigente');
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

    public function listarVigentes($id, $condicion)
    {
        try {
            if($condicion == 1) {
                $portadas = Portada::select('id', 'imagenEsc', 'imagenCel', 'TextoBtn', 'UrlBtn', 'Titulo', 'Descripcion')
                    ->where('identificadorPadre', $id)
                    ->where('identificadorHijo', '!=', 0)
                    ->where('Vigente', 1)
                    ->get();

            }else{
                $portadas = Portada::select('id', 'imagenEsc', 'imagenCel', 'TextoBtn', 'UrlBtn', 'Titulo', 'Descripcion')
                    ->where('identificadorPadre', $id)
                    ->where('Vigente', 1)
                    ->get();
            }

            return response()->json($portadas);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las portadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function listarSedes()
    {
        try{
            $sedes = Sede::select('id', 'nombre')
            ->where('vigente', 1)
            ->get();
            return response()->json($sedes);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al obtener las sedes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
