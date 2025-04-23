<?php

namespace App\Http\Controllers\API\AtencionCliente;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\EmbarazoPrevio;
use App\Models\AtencionCliente\Paciente;
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

    public function buscarPersona(Request $request)
    {
        try{

            // Verificar existencia
            $existe = DB::table('personas')
                ->where('NumeroDocumento', $request->numeroDocumento)
                ->where('CodigoTipoDocumento', $request->tipoDocumento)
                ->exists();

            if ($existe) {
                // Obtener datos si existe
                $persona = DB::table('personas')
                            ->select('Codigo', 'Nombres', 'Apellidos', 'Direccion', 'Celular', 'Correo', 'NumeroDocumento')
                            ->where('NumeroDocumento', $request->numeroDocumento)
                            ->where('CodigoTipoDocumento', $request->tipoDocumento)
                            ->first();
            }else{
                $persona = null;
            }

            return response()->json([
                'existe' => $existe,
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
        
        DB::beginTransaction();
        try{
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
    
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Paciente registrado correctamente.']);
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
