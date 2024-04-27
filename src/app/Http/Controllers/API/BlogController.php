<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\ActualizarBlogRequest;
use App\Http\Requests\Blog\RegistroBlogRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;

class BlogController extends Controller
{

    private function getUploadConfig($request)
    {
        $file = $request->file('Imagen');

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

        $remoteFileName = 'imagenes/' . uniqid() . '.' . $config['file']->getClientOriginalExtension();

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


    // Método para eliminar un archivo del bucket en Google Cloud Storage
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
        return BlogResource::collection(Blog::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    //Movi aqui xd
    public function updatePost(ActualizarBlogRequest $request)
    {

        try {
            $blog = Blog::find($request->input('id'));

            if (!$blog) {
                return response()->json([
                    'error' => 'Blog no encontrado'
                ], 404);
            }

            if ($request->hasFile('Imagen')) {

                $uploadConfig = $this->getUploadConfig($request);
                $url = $this->uploadFile($uploadConfig);

                if ($blog->Imagen != null && $this->fileExists($blog->Imagen)) {
                    $this->deleteFile($blog->Imagen);
                }

                $blog->Imagen = $url;
            }

            $blog->Titulo = $request->input('Titulo');
            $blog->Descripcion = $request->input('Descripcion');
            $blog->vigente = $request->input('vigente');
            $blog->save();

            return response()->json([
                'msg' => 'Blog actualizado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el blog. ' . $e->getMessage()
            ], 500);
        }
    }



    public function store(RegistroBlogRequest $request)
    {
        try {
            $fechaActual = date('d/m/Y');
            $uploadConfig = $this->getUploadConfig($request);

            // Subir el archivo a Google Cloud Storage
            $url = $this->uploadFile($uploadConfig);

            $blog = new Blog($request->all());
            $blog->Titulo = $request->input('Titulo');
            $blog->Fecha = $fechaActual;
            $blog->Imagen = $url;
            $blog->Descripcion = $request->input('Descripcion');

            $blog->save();

            return response()->json([
                'msg' => 'Blog registrado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al registrar el blog. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Blog $blog)
    {
        return new BlogResource($blog);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActualizarBlogRequest $request, Blog $blog)
    {
        $blog->update($request->all());
        return (new BlogResource($blog))
            ->additional(['msg' => 'Blog actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        try {

            if ($blog->Imagen != null && $this->fileExists($blog->Imagen)) {
                $this->deleteFile($blog->Imagen);
            }

            $blog->delete();
            return response()->json([
                'msg' => 'Blog eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al eliminar el médico: ' . $e->getMessage()
            ], 500);
        }
    }
}
