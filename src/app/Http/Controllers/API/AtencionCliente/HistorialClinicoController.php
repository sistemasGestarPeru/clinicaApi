<?php

namespace App\Http\Controllers\API\AtencionCliente;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\HistorialClinico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function buscarPacienteHistorial(Request $request)
    {
        try {

            $query = DB::table('personas as p')
                ->join('paciente as pa', 'p.Codigo', '=', 'pa.Codigo')
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.NumeroDocumento AS Documento',
                    'p.CodigoTipoDocumento AS tipoDoc',
                    'pa.Genero',
                    DB::raw("CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM historialclinico 
                        WHERE CodigoPaciente01 = p.Codigo OR CodigoPaciente02 = p.Codigo
                    ) THEN 1
                    ELSE 0
                END AS Existe")
                )
                ->where('p.Vigente', 1)
                ->when($request->filled('tipoDoc'), function ($query) use ($request) {
                    $query->where('p.CodigoTipoDocumento', $request->tipoDoc);
                })
                ->when($request->filled('termino'), function ($query) use ($request) {
                    $termino = $request->termino . '%';
                    $query->where(function ($q) use ($termino) {
                        $q->where('p.NumeroDocumento', 'LIKE', $termino)
                            ->orWhere('p.Nombres', 'LIKE', $termino)
                            ->orWhere('p.Apellidos', 'LIKE', $termino);
                    });
                })
                ->when($request->genero === 'F', function ($query) use ($request) {
                    $query->where('pa.Genero', $request->genero);
                });

            $pacientes = $query->get();

            // Log de éxito
            Log::info('Pacientes listados correctamente', [
                'Conttrolador' => 'HistorialClinicoController',
                'Metodo' => 'buscarPacienteHistorial',
                'cantidad' => count($pacientes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($pacientes, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al buscar Paciente.', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'buscarPacienteHistorial',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['msg' => 'Error al buscar Paciente.', 'bd' => $e->getMessage()], 500);
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

            // Log de éxito
            Log::info('Historial Clínico listado correctamente', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'listarHistorial',
                'cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al listar Historial Clínico.', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'listarHistorial',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al listar Color Ojos.', 'bd' => $e->getMessage()], 500);
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
            // Log de éxito
            Log::info('Historial Clínico registrado correctamente', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'registrarHistorial',
                'codigo_paciente_01' => $request->CodigoPaciente01,
                'codigo_paciente_02' => $request->CodigoPaciente02,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Historial Clínico registrado correctamente.'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al registrar Historial Clínico.', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'registrarHistorial',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al registrar Historial Clínico.', 'bd' => $e->getMessage()], 500);
        }
    }


    public function consultarHistorial($codigo)
    {
        try {
            $entidad = HistorialClinico::where('Codigo', $codigo)->first();
            if (!$entidad) {
                // Log del error específico
                Log::warning('Historial Clínico no encontrado', [
                    'Controlador' => 'HistorialClinicoController',
                    'Metodo' => 'consultarHistorial',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['msg' => 'Historial Clínico no encontrado.'], 404);
            }
            // Log de éxito
            Log::info('Historial Clínico consultado correctamente', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'consultarHistorial',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al consultar Historial Clínico.', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'consultarHistorial',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al consultar Historial Clinico.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarHistorial(Request $request)
    {
        try {
            $historial = HistorialClinico::findOrFail($request->Codigo);

            $historial->update($request->all());
            // Log de éxito
            Log::info('Historial Clínico actualizado correctamente', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'actualizarHistorial',
                'codigo' => $historial->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Historial Clínico actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al actualizar Historial Clínico.', [
                'Controlador' => 'HistorialClinicoController',
                'Metodo' => 'actualizarHistorial',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Error al actualizar Historial Clinico.', 'bd' => $e->getMessage()], 500);
        }
    }
}
