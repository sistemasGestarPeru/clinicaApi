<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medico\ActualizarMedicoRequest;
use App\Http\Requests\Medico\GuardarMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Models\Medico;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function listarMedicos($tipo)
    {
        try{
            $medicos = DB::table('medicos')
                ->select(
                    'id',
                    DB::raw("CONCAT(nombre, ' ', apellidoPaterno, ' ', apellidoMaterno) as nombres"),
                    'genero',
                    'imagen',
                    'vigente'
                )
                ->where('tipo', $tipo)
                ->get();
            return response()->json($medicos, 200);

        }catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Ocurrió un error al listar los médicos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarMedico($id){
        try {

            $medico = Medico::findOrFail($id);
            if (!$medico) {
                return response()->json([
                    'mensaje' => 'Médico no encontrado'
                ], 404);
            }
            return response()->json($medico, 200);

        } catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Ocurrió un error al consultar los médicos',
                'error' => $e->getMessage()
            ], 500);
        }
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
            $medico->CPSP = $request->input('CPSP'); 
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
            $medico->CPSP = $request->input('CPSP');
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
        try{

            $medicos = DB::table('medicos')
                ->join('sedes', 'medicos.sede_id', '=', 'sedes.id')
                ->select(
                    'medicos.nombre',
                    'medicos.apellidoPaterno',
                    'medicos.apellidoMaterno',
                    'medicos.genero',
                    'medicos.imagen',
                    'medicos.CMP',
                    'medicos.RNE',
                    'sedes.nombre as sede_id',
                    'medicos.descripcion'
                )
                ->where('medicos.tipo', 0)
                ->where('medicos.vigente', 1)
                ->orderBy('medicos.id', 'asc')
                ->get();

            return response()->json($medicos, 200);
            
        }catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Ocurrió un error al listar los ginecólogos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarBiologosVigentes()
    {
        try{

            $medicos = DB::table('medicos')
                ->join('sedes', 'medicos.sede_id', '=', 'sedes.id')
                ->select(
                    'medicos.nombre',
                    'medicos.apellidoPaterno',
                    'medicos.apellidoMaterno',
                    'medicos.genero',
                    'medicos.imagen',
                    'medicos.CMP',
                    'medicos.RNE',
                    'sedes.nombre as sede_id',
                    'medicos.descripcion'
                )
                ->where('medicos.tipo', 1)
                ->where('medicos.vigente', 1)
                ->orderBy('medicos.id', 'asc')
                ->get();

            return response()->json($medicos, 200);
            
        }catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Ocurrió un error al listar los biólogos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarPsicologosVigentes(){
        try{
            $medicos = DB::table('medicos')
                ->join('sedes', 'medicos.sede_id', '=', 'sedes.id')
                ->select(
                    'medicos.nombre',
                    'medicos.apellidoPaterno',
                    'medicos.apellidoMaterno',
                    'medicos.genero',
                    'medicos.imagen',
                    'medicos.CMP',
                    'medicos.RNE',
                    'sedes.nombre as sede_id',
                    'medicos.descripcion'
                )
                ->where('medicos.tipo', 2)
                ->where('medicos.vigente', 1)
                ->orderBy('medicos.id', 'asc')
                ->get();

            return response()->json($medicos, 200);
            
        }catch (\Exception $e) {
            return response()->json([
                'mensaje' => 'Ocurrió un error al listar los psicólogos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
