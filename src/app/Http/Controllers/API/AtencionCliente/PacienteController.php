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
use Illuminate\Support\Facades\Log;
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

            // Log de éxito
            Log::info('Paciente listados correctamente', [
                'cantidad' => count($pacientes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($pacientes, 200);

        }catch(\Exception $e){
            // Log de error
            Log::error('Error al listar pacientes', [
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
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

                Log::warning('Paciente no encontrado.', [
                    'Codigo' => $codigo
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Paciente no encontrado.'
                ], 404);
            }

            $paciente = Paciente::findOrFail($codigo);

            if(!$paciente){
                Log::warning('Paciente no encontrado.', [
                    'Codigo' => $codigo
                ]);
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

            // Log de éxito
            Log::info('Paciente consultado correctamente', [
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'persona' => $persona,
                'paciente' => $paciente,
                'embarazoPrevio' => $embarazosPrevios,
                'varon' => $varon
            ], 200);
        }catch(\Exception $e){
            // Log de error
            Log::error('Error al consultar paciente', [
                'Codigo' => $codigo,
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
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
                // Log de éxito
                Log::info('Paciente Encontrado', [
                    'paciente' => ($pacienteData),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

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
                // Log de éxito
                Log::info('Persona encontrada', [
                    'numeroDocumento' => $request->numeroDocumento,
                    'tipoDocumento' => $request->tipoDocumento,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                // Obtener datos si existe
                $persona = DB::table('personas')
                            ->select('Codigo', 'Nombres', 'Apellidos', 'Direccion', 'Celular', 'Correo', 'NumeroDocumento', 'CodigoTipoDocumento', 'CodigoNacionalidad')
                            ->where('NumeroDocumento', $request->numeroDocumento)
                            ->where('CodigoTipoDocumento', $request->tipoDocumento)
                            ->first();
            }else{
                $persona = null;
            }
            // Log de éxito
            Log::info('Búsqueda de persona realizada', [
                'numeroDocumento' => $request->numeroDocumento,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'existe' => $existePersona,
                'data' => $persona
            ], 200);

        }catch(\Exception $e){
            // Log de error
            Log::error('Error al buscar persona', [
                'numeroDocumento' => $request->numeroDocumento,
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
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

            // Log de éxito
            Log::info('Paciente registrado correctamente', [
                'paciente' => $paciente['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
    
            DB::commit();
            
            return response()->json(['persona' => $paciente['Codigo'], 'message' => 'Paciente registrado correctamente.']);
        }catch(\Exception $e){
            DB::rollback();
            // Log de error
            Log::error('Error al registrar paciente', [
                'paciente' => $paciente['Codigo'],
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el paciente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

}
