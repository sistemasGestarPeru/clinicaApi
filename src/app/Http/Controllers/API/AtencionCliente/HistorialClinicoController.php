<?php

namespace App\Http\Controllers\API\AtencionCliente;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\HistorialClinico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorialClinicoController extends Controller
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

    public function buscarPacienteHistorial(Request $request){
        try{

            $subQuery = DB::table('historialclinico')
            ->select('CodigoPaciente01')
            ->whereNotNull('CodigoPaciente01')
            ->union(
                DB::table('historialclinico')
                    ->select('CodigoPaciente02')
                    ->whereNotNull('CodigoPaciente02')
            );
        
            $pacientes = DB::table('paciente as pa')
                ->join('personas as p', 'pa.Codigo', '=', 'p.Codigo')
                ->where('p.Vigente', 1)
                ->where('p.CodigoTipoDocumento', $request->tipoDoc)
                ->when($request->genero === 'F', function ($query) use ($request) {
                    $query->where('pa.Genero', $request->genero);
                })
                ->whereNotIn('p.Codigo', $subQuery)
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.NumeroDocumento as Documento',
                    'p.CodigoTipoDocumento as tipoDoc',
                    'pa.Genero'
                )
                ->get();
        

            return response()->json($pacientes, 200);

        }catch(\Exception $e){
            return response()->json(['msg' => 'Error al buscar Paciente.' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function listarHistorial(Request $request)
    {
        try {
            $entidad = DB::table('historialclinico as hc')
            ->join('personas as p1', 'hc.CodigoPaciente01', '=', 'p1.Codigo')
            ->leftJoin('personas as p2', 'hc.CodigoPaciente02', '=', 'p2.Codigo')
            ->select(
                'hc.Codigo',
                'hc.Numero as numero',
                DB::raw("CONCAT(p1.Nombres, ' ', p1.Apellidos) as paciente1"),
                DB::raw("CONCAT(p2.Nombres, ' ', p2.Apellidos) as paciente2")
            )
            ->get();
        
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error al listar Color Ojos.' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarHistorial(Request $request)
    {
        // Validar paciente 01 obligatorio
        if (empty($request->CodigoPaciente01) || $request->CodigoPaciente01 == 0) {
            return response()->json(['msg' => 'El campo CodigoPaciente01 es obligatorio.'], 422);
        }
    
        // Si paciente 02 es 0 o null, lo asignamos como null
        if (empty($request->CodigoPaciente02) || $request->CodigoPaciente02 == 0) {
            $request->merge(['CodigoPaciente02' => null]);
        }
    
        // Validar que los códigos no sean iguales
        if ($request->CodigoPaciente01 == $request->CodigoPaciente02 && $request->CodigoPaciente02 !== null) {
            return response()->json(['msg' => 'Los pacientes no pueden ser los mismos.'], 422);
        }
    
        // Guardar historial
        try {
            HistorialClinico::create($request->all());
            return response()->json(['msg' => 'Historial Clínico registrado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error al registrar Historial Clínico.', 'bd' => $e->getMessage()], 500);
        }
    }
    

    public function consultarHistorial($codigo){
        try{
            $entidad = HistorialClinico::where('Codigo', $codigo)->first();
            return response()->json($entidad, 200);
        }catch(\Exception $e){
            return response()->json(['msg' => 'Error al consultar Historial Clinico.' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarHistorial(Request $request){
        try{
            $historial = HistorialClinico::findOrFail($request->Codigo);
            $historial->update($request->all());

        }catch(\Exception $e){
            return response()->json(['msg' => 'Error al actualizar Historial Clinico.' ,'bd' => $e->getMessage()], 500);
        }
    }

}
