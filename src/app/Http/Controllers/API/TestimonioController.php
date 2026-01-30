<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Testimonio\ActualizarTestimonioRequest;
use App\Http\Requests\Testimonio\GuardarTestimonioRequest;
use App\Http\Resources\TestimonioResource;
use App\Models\Testimonio;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestimonioController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // Método para configurar la subida del archivo
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

        $remoteFileName = 'Testimonios/' . uniqid() . '.' . $config['file']->getClientOriginalExtension();

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
     * Store a newly created resource in storage.
     */
    public function store(GuardarTestimonioRequest $request)
    {
        try {
            // Configuración para subir el archivo
            $uploadConfig = $this->getUploadConfig($request);

            // Subir el archivo a Google Cloud Storage
            $url = $this->uploadFile($uploadConfig);

            // Crear una nueva instancia del modelo Testimonio con los datos validados del formulario
            $testimonio = new Testimonio();

            // Llenar los campos del modelo con los datos del formulario
            $testimonio->nombre = $request->input('nombre');
            $testimonio->apellidoPaterno = $request->input('apellidoPaterno');
            $testimonio->apellidoMaterno = $request->input('apellidoMaterno');
            $testimonio->sede_id = $request->input('sede_id');
            $testimonio->descripcion = $request->input('descripcion');
            $testimonio->fecha = $request->input('fecha');

            // Asignar la URL del archivo en Google Cloud Storage al campo 'imagen'
            $testimonio->imagen = $url;

            // Guardar el testimonio en la base de datos
            $testimonio->save();

            // Retornar una respuesta indicando que el testimonio se guardó correctamente
            return response()->json([
                'msg' => 'Testimonio guardado correctamente'
            ]);
        } catch (\Exception $e) {
            // En caso de que ocurra una excepción, manejarla aquí
            // Puedes registrar el error, devolver un mensaje de error personalizado, etc.
            return response()->json([
                'error' => 'Ocurrió un error al guardar el testimonio: ' . $e->getMessage()
            ], 500); // Código de error 500 para indicar un error interno del servidor
        }
    }

    /**
     * Update the specified resource in storage.
     */

    public function updatePost(ActualizarTestimonioRequest $request)
    {
        try {
            // Buscar el testimonio en la base de datos por su ID
            $testimonio = Testimonio::find($request->input('id'));

            // Verificar si se encontró el testimonio
            if (!$testimonio) {
                return response()->json([
                    'error' => 'No se encontró el testimonio con el ID proporcionado'
                ], 404); // Código de error 404 para indicar recurso no encontrado
            }

            // Verificar si se ha proporcionado una nueva imagen
            if ($request->hasFile('imagen')) {
                // Configuración para subir el archivo
                $uploadConfig = $this->getUploadConfig($request);

                // Subir el nuevo archivo a Google Cloud Storage
                $url = $this->uploadFile($uploadConfig);

                // Eliminar la imagen anterior de Cloud Storage

                if ($testimonio->imagen != null && $this->fileExists($testimonio->imagen)) {
                    $this->deleteFile($testimonio->imagen);
                }

                // Actualizar la URL del archivo en Google Cloud Storage
                $testimonio->imagen = $url;
            }

            // Actualizar los demás campos del testimonio con los datos del formulario
            $testimonio->nombre = $request->input('nombre');
            $testimonio->apellidoPaterno = $request->input('apellidoPaterno');
            $testimonio->apellidoMaterno = $request->input('apellidoMaterno');
            $testimonio->sede_id = $request->input('sede_id');
            $testimonio->descripcion = $request->input('descripcion');
            $testimonio->fecha = $request->input('fecha');
            $testimonio->vigente = $request->input('vigente');

            // Guardar los cambios en la base de datos
            $testimonio->save();

            // Retornar una respuesta indicando que el testimonio se actualizó correctamente
            return response()->json([
                'msg' => 'Testimonio actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            // En caso de que ocurra una excepción, manejarla aquí
            // Puedes registrar el error, devolver un mensaje de error personalizado, etc.
            return response()->json([
                'error' => 'Ocurrió un error al actualizar el testimonio: ' . $e->getMessage()
            ], 500); // Código de error 500 para indicar un error interno del servidor
        }
    }

    public function index()
    {
        $testimonios = Testimonio::all(
            [
                'id',
                'nombre',
                'apellidoPaterno',
                'apellidoMaterno',
                'sede_id',
                'descripcion',
                'imagen',
                'fecha',
                'vigente'
            ]
        );

        $testimoniosArray = [];
        foreach ($testimonios as $testimonio) {
            $testimoniosArray[] = [
                'id' => $testimonio->id,
                'nombre' => $testimonio->nombre,
                'apellidoPaterno' => $testimonio->apellidoPaterno,
                'apellidoMaterno' => $testimonio->apellidoMaterno,
                'sede_id' => $testimonio->sede_id,
                'descripcion' => $testimonio->descripcion,
                'imagen' => $testimonio->imagen,
                'fecha' => $testimonio->fecha,
                'vigente' => $testimonio->vigente
            ];
        }

        return $testimoniosArray;
    }

    /**
     * Display the specified resource.
     */
    public function show(Testimonio $testimonio)
    {
        return new TestimonioResource($testimonio);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Testimonio $testimonio)
    {
        try {
            // Eliminar la imagen asociada en Cloud Storage
            if ($testimonio->imagen != null && $this->fileExists($testimonio->imagen)) {
                $this->deleteFile($testimonio->imagen);
            }

            // Eliminar el testimonio de la base de datos
            $testimonio->delete();

            return (new TestimonioResource($testimonio))
                ->additional(['msg' => 'Testimonio eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al eliminar el testimonio: ' . $e->getMessage()
            ], 500);
        }
    }

    // public function listarUltimos()
    // {
    //     try{
    //         $testimonios = DB::table('testimonios as t')
    //             ->join(DB::raw('(
    //                 SELECT sede_id, MAX(Fecha) AS max_fecha
    //                 FROM testimonios
    //                 WHERE vigente = 1
    //                 GROUP BY sede_id
    //             ) as ultimos'), function ($join) {
    //                 $join->on('t.sede_id', '=', 'ultimos.sede_id')
    //                     ->on('t.Fecha', '=', 'ultimos.max_fecha');
    //             })
    //             ->join('sedes as s', 's.id', '=', 't.sede_id')
    //             ->where('s.vigente', 1)
    //             ->select('t.descripcion', 't.imagen', DB::raw('s.nombre as sede_id'))
    //             ->get();
    //         return response()->json($testimonios, 200);
            
    //     }catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Ocurrió un error al listar los testimonios: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function listarUltimos()
    {
        try {
            $testimonios = DB::table('testimonios as t')
                ->join('sedes as s', 's.id', '=', 't.sede_id')
                ->where('t.vigente', 1)
                ->where('s.vigente', 1)
                ->whereRaw('t.id = (
                    SELECT t2.id
                    FROM testimonios t2
                    WHERE t2.sede_id = t.sede_id
                    AND t2.vigente = 1
                    ORDER BY t2.Fecha DESC, t2.id DESC
                    LIMIT 1
                )')
                ->select(
                    't.descripcion',
                    't.imagen',
                    DB::raw('s.nombre as sede_id')
                )
                ->get();

            return response()->json($testimonios, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al listar los testimonios: ' . $e->getMessage()
            ], 500);
        }
    }


    public function listarVigente()
    {
        $testimonios = Testimonio::with('sede')
            ->where('vigente', true)
            ->orderBy('id', 'desc')
            ->get();

        $testimoniosArray = [];
        foreach ($testimonios as $testimonio) {
            $testimoniosArray[] = [
                // 'nombre' => $testimonio->nombre,
                // 'apellidoPaterno' => $testimonio->apellidoPaterno,
                // 'apellidoMaterno' => $testimonio->apellidoMaterno,
                'imagen' => $testimonio->imagen,
                'sede_id' => $testimonio->sede->nombre,
                'descripcion' => $testimonio->descripcion,
                'Fecha' => $testimonio->Fecha
            ];
        }

        return $testimoniosArray;
    }
}
