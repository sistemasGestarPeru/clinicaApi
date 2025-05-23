<?php

namespace App\Http\Controllers\API\AtencionCliente;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\EmbarazoPrevio;
use App\Models\AtencionCliente\Paciente;
use App\Models\AtencionCliente\Varon;
use App\Models\Personal\Persona;
use Faker\Provider\ar_EG\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PacienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

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

    public function listarPacientes(Request $request){

        try{

            $pacientes = DB::table('paciente as pa')
            ->join('personas as p', 'pa.Codigo', '=', 'p.Codigo')
            ->select(
                'pa.Codigo',
                'pa.FechaRegistro',
                DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Paciente"),
                'pa.EstadoCivil',
                'pa.Peso',
                'pa.Altura',
                DB::raw("CONCAT(pa.GrupoSanguineo, ' ',
                            CASE 
                                WHEN pa.RH = 1 THEN '+'
                                ELSE '-'
                            END
                        ) as GrupoSanguineo"),
                'pa.FechaNacimiento',
                DB::raw("CASE 
                            WHEN pa.Genero = 'F' THEN 'Femenino'
                            WHEN pa.Genero = 'M' THEN 'Masculino'
                            ELSE 'Desconocido'
                         END as Genero")
            )
            ->get();

            return response()->json($pacientes, 200);

        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Error al listar los pacientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarPaciente($codigo){
        try{

            $persona = Persona::findOrFail($codigo);
            $embarazosPrevios = [];
            $varon = null;

            if(!$persona){
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado.'
                ], 404);
            }

            $paciente = Paciente::findOrFail($codigo);

            if(!$paciente){
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado.'
                ], 404);
            }

            if($paciente && $paciente->Genero == 'F'){
                $embarazosPrevios = EmbarazoPrevio::where('CodigoMujer', $codigo)->get();
            }

            if($paciente && $paciente->Genero == 'M'){
                $varon = Varon::where('Codigo', $codigo)->first();
            }


            return response()->json([
                'persona' => $persona,
                'paciente' => $paciente,
                'embarazoPrevio' => $embarazosPrevios,
                'varon' => $varon
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el paciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buscarPersona(Request $request)
    {
        try{

            //Validar si existe el paciente

            $pacienteData = DB::table('paciente as pa')
            ->join('personas as p', 'pa.Codigo', '=', 'p.Codigo')
            ->where('p.CodigoTipoDocumento', $request->tipoDocumento)
            ->where('p.NumeroDocumento', $request->numeroDocumento)
            ->select('p.Codigo', 'pa.Genero')
            ->first();

            if($pacienteData){
                // Si existe, retornar el codigo del paciente 
                return response()->json([
                    'paciente' => $pacienteData
                ], 200);
            }


            // Verificar existencia en tabla persona
            $existePersona = DB::table('personas')
                ->where('NumeroDocumento', $request->numeroDocumento)
                ->where('CodigoTipoDocumento', $request->tipoDocumento)
                ->exists();

            if ($existePersona) {
                // Obtener datos si existe
                $persona = DB::table('personas')
                            ->select('Codigo', 'Nombres', 'Apellidos', 'Direccion', 'Celular', 'Correo', 'NumeroDocumento', 'CodigoTipoDocumento', 'CodigoNacionalidad')
                            ->where('NumeroDocumento', $request->numeroDocumento)
                            ->where('CodigoTipoDocumento', $request->tipoDocumento)
                            ->first();
            }else{
                $persona = null;
            }

            return response()->json([
                'existe' => $existePersona,
                'data' => $persona
            ], 200);

        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar la persona',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function registrarPaciente(Request $request){

        date_default_timezone_set('America/Lima');
        $diaActual = date('d');

        $query = $request->input('query'); 

        $paciente = $query['Paciente'];
        $embarazosPrevios = $query['EmbarazosPrevios'] ?? [];
        $varon = $query['Varon'] ?? null;
        $persona = $query['Persona'] ?? null;

        $codp = 0;
        DB::beginTransaction();
        try{

            if($paciente['Codigo'] == null || $paciente['Codigo'] == 0){
                $codp = Persona::create($persona)->Codigo;
                $paciente['Codigo'] = $codp;
            }

            // Insertar paciente
            Paciente::create($paciente);
    
            // Insertar embarazos previos si es mujer y hay datos
            if($paciente['Genero'] == 'F' && !empty($embarazosPrevios)){
                
                foreach ($embarazosPrevios as $embarazo) {
                    $embarazo['Fecha'] = $embarazo['Fecha'] . '-' . $diaActual;

                    $embarazo['CodigoMujer'] = $paciente['Codigo'];
                    EmbarazoPrevio::create($embarazo);
                }
            }

            if($paciente['Genero'] == 'M' && $varon != null){
                // Insertar datos del varon
                $varon['Codigo'] = $paciente['Codigo'];
                Varon::create($varon);
            }
    
            DB::commit();
            return response()->json(['persona' => $paciente['Codigo'], 'message' => 'Paciente registrado correctamente.']);
        }catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el paciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
