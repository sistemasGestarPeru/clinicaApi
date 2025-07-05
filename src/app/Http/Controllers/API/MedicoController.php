<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medico\ActualizarMedicoRequest;
use App\Http\Requests\Medico\GuardarMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Models\Medico;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;

class MedicoController extends Controller
{

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
    public function index(Request $request)
    {

        $tipo = $request->input('tipo'); // Obtener el parámetro 'tipo' de la solicitud

        // Verificar si se proporcionó el parámetro 'tipo' y filtrar los médicos en consecuencia
        if ($tipo !== null) {
            $medicos = Medico::where('tipo', $tipo)->get();
        } else {
            // Si no se proporciona el parámetro 'tipo', mostrar todos los médicos
            $medicos = Medico::all();
        }

        $medicosArray = [];
        foreach ($medicos as $medico) {
            $medicosArray[] = [
                'nombre' => $medico->nombre,
                'apellidoPaterno' => $medico->apellidoPaterno,
                'apellidoMaterno' => $medico->apellidoMaterno,
                'genero' => $medico->genero,
                'imagen' => $medico->imagen,
                'vigente' => $medico->vigente,
            ];
        }

        return $medicosArray;
    }

    public function listarGinecologos()
    {
        $medicos = Medico::with('sede')
            ->where('tipo', 0)
            ->orderBy('id', 'asc')
            ->get();

        $medicosArray = [];

        foreach ($medicos as $medico) {
            $medicosArray[] = [
                'id' => $medico->id,
                'nombre' => $medico->nombre,
                'apellidoPaterno' => $medico->apellidoPaterno,
                'apellidoMaterno' => $medico->apellidoMaterno,
                'CMP' => $medico->CMP,
                'RNE' => $medico->RNE,
                'linkedin' => $medico->linkedin,
                'descripcion' => $medico->descripcion,
                'sede_id' => $medico->sede_id,
                'genero' => $medico->genero,
                'imagen' => $medico->imagen,
                'vigente' => $medico->vigente,
            ];
        }

        return $medicosArray;
    }

    public function listarBiologos()
    {
        $medicos = Medico::with('sede')
            ->where('tipo', 1)
            ->orderBy('id', 'asc')
            ->get();

        $medicosArray = [];

        foreach ($medicos as $medico) {
            $medicosArray[] = [
                'id' => $medico->id,
                'nombre' => $medico->nombre,
                'apellidoPaterno' => $medico->apellidoPaterno,
                'apellidoMaterno' => $medico->apellidoMaterno,
                'CBP' => $medico->CBP,
                'sede_id' => $medico->sede_id,
                'descripcion' => $medico->descripcion,
                'linkedin' => $medico->linkedin,
                'genero' => $medico->genero,
                'imagen' => $medico->imagen,
                'vigente' => $medico->vigente,
            ];
        }

        return $medicosArray;
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(GuardarMedicoRequest $request)
    {
        try {


            $medico = new Medico($request->all());

            // Obtener el valor de linkedin del request
            $linkedin = $request->input('linkedin');

            // Verificar si el valor es 'undefined' o un espacio en blanco
            if ($linkedin === 'undefined' || trim($linkedin) === '' || $linkedin === 'null') {
                $medico->linkedin = null; // Guardar null si el valor no es válido
            } else {
                $medico->linkedin = $linkedin; // Guardar el valor proporcionado
            }

            $medico->nombre = $request->input('nombre');
            $medico->apellidoPaterno = $request->input('apellidoPaterno');
            $medico->apellidoMaterno = $request->input('apellidoMaterno');
            $medico->genero = $request->input('genero');

            $medico->descripcion = $request->input('descripcion');
            $medico->CMP = $request->input('CMP');
            $medico->RNE = $request->input('RNE');
            $medico->CBP = $request->input('CBP');
            $medico->tipo = $request->input('tipo');
            $medico->sede_id = $request->input('sede_id');

            $uploadConfig = $this->getUploadConfig($request);

            // Subir el archivo a Google Cloud Storage
            $url = $this->uploadFile($uploadConfig);
            $medico->imagen = $url;

            $medico->save();

            return response()->json([
                'msg' => 'Médico guardado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al guardar el testimonio: ' . $e->getMessage()
            ], 500);
        }

        // return (new MedicoResource(Medico::create($request->all())))
        //     ->additional(['msg' => 'Medico guardado correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Medico $medico)
    {
        return new MedicoResource($medico);
    }

    /**
     * Update the specified resource in storage.
     */

    public function updatePost(ActualizarMedicoRequest $request)
    {

        try {

            $medico = Medico::find($request->input('id'));

            if (!$medico) {
                return response()->json([
                    'error' => 'No se encontró el médico'
                ], 404);
            }

            $medico->nombre = $request->input('nombre');
            $medico->apellidoPaterno = $request->input('apellidoPaterno');
            $medico->apellidoMaterno = $request->input('apellidoMaterno');
            $medico->genero = $request->input('genero');
            $medico->CMP = $request->input('CMP');
            $medico->RNE = $request->input('RNE');
            $medico->CBP = $request->input('CBP');
            $medico->descripcion = $request->input('descripcion');

            // Obtener el valor de linkedin del request
            $linkedin = $request->input('linkedin');

            // Verificar si el valor es 'undefined' o un espacio en blanco
            if ($linkedin === 'undefined' || trim($linkedin) === '' || $linkedin === 'null') {
                $medico->linkedin = null; // Guardar null si el valor no es válido
            } else {
                $medico->linkedin = $linkedin; // Guardar el valor proporcionado
            }

            $medico->sede_id = $request->input('sede_id');
            $medico->vigente = $request->input('vigente');

            if ($request->hasFile('imagen')) {
                $uploadConfig = $this->getUploadConfig($request);
                $url = $this->uploadFile($uploadConfig);

                if ($medico->imagen != null && $this->fileExists($medico->imagen)) {
                    $this->deleteFile($medico->imagen);
                }

                $medico->imagen = $url;
            }

            $medico->save();

            return response()->json([
                'msg' => 'Médico actualizado correctamente'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Ocurrió un error al actualizar el médico: ' . $e->getMessage()
            ], 500);
        }
    }



    public function update(ActualizarMedicoRequest $request, Medico $medico)
    {
        try {
            $medico = Medico::find($request->input('id'));

            if (!$medico) {
                return response()->json([
                    'error' => 'No se encontró el médico'
                ], 404);
            }

            $medico->nombre = $request->input('nombre');
            $medico->apellidoPaterno = $request->input('apellidoPaterno');
            $medico->apellidoMaterno = $request->input('apellidoMaterno');
            $medico->genero = $request->input('genero');
            $medico->CMP = $request->input('CMP');
            $medico->RNE = $request->input('RNE');
            $medico->descripcion = $request->input('descripcion');
            $medico->linkedin = $request->input('linkedin');
            $medico->sede_id = $request->input('sede_id');

            if ($request->hasFile('imagen')) {
                $uploadConfig = $this->getUploadConfig($request);
                $url = $this->uploadFile($uploadConfig);
                $this->deleteFile($medico->imagen);
                $medico->imagen = $url;
            }

            $medico->save();

            return response()->json([
                'msg' => 'Médico actualizado correctamente'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Ocurrió un error al actualizar el médico: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Medico $medico)
    {
        try {

            if ($medico->imagen != null && $this->fileExists($medico->imagen)) {
                $this->deleteFile($medico->imagen);
            }

            $medico->delete();

            return (new MedicoResource($medico))
                ->additional(['msg' => 'Médico eliminado correctamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ocurrió un error al eliminar el médico: ' . $e->getMessage()
            ], 500);
        }
    }


    public function listarGinecologosVigentes()
    {
        $ginecologo = Medico::with('sede')
            ->where('tipo', 0)
            ->where('vigente', 1)
            ->orderBy('id', 'asc')
            ->get();

        $ginecologosArray = [];

        foreach ($ginecologo as $medico) {
            $ginecologosArray[] = [
                'nombre' => $medico->nombre,
                'apellidoPaterno' => $medico->apellidoPaterno,
                'apellidoMaterno' => $medico->apellidoMaterno,
                'genero' => $medico->genero,
                'imagen' => $medico->imagen,
                'CMP' => $medico->CMP,
                'RNE' => $medico->RNE,
                'sede_id' => $medico->sede->nombre,
                'descripcion' => $medico->descripcion,
            ];
        }

        return $ginecologosArray;
    }

    public function listarBiologosVigentes()
    {
        $biologo = Medico::with('sede')
            ->where('tipo', 1)
            ->where('vigente', 1)
            ->orderBy('id', 'asc')
            ->get();

        $biologosArray = [];

        foreach ($biologo as $medico) {
            $biologosArray[] = [
                'nombre' => $medico->nombre,
                'apellidoPaterno' => $medico->apellidoPaterno,
                'apellidoMaterno' => $medico->apellidoMaterno,
                'genero' => $medico->genero,
                'imagen' => $medico->imagen,
                'CBP' => $medico->CBP,
                'sede_id' => $medico->sede->nombre,
                'descripcion' => $medico->descripcion,
            ];
        }

        return $biologosArray;
    }
}
