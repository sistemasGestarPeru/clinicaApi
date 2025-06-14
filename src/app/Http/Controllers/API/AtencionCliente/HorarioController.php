<?php

namespace App\Http\Controllers\API\AtencionCliente;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HorarioController extends Controller
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


    public function listarHorarios(Request $request)
    {
        try {
            $horarios = Horario::select('Codigo', 'CodigoMedico', 'Fecha', 'HoraInicio', 'HoraFin')
                ->where('Vigente', 1)
                ->where('CodigoSede', $request->Sede)
                ->get();

            // Log de éxito
            Log::info('Horarios listados correctamente', [
                'Controlador' => 'HorarioController',
                'Metodo' => 'listarHorarios',
                'cantidad' => count($horarios),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($horarios, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error al listar los horarios', [
                'Controlador' => 'HorarioController',
                'Metodo' => 'listarHorarios',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'msg' => 'Error al listar los horarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // public function listarHorarios(Request $request)
    // {

    //     try {

    //         $query = Horario::where('Vigente', 1);

    //         // Filtro por médico (solo si no es 0)
    //         if ($request->Medico != 0) {
    //             $query->where('CodigoMedico', $request->Medico);
    //         }

    //         // Filtro por sede
    //         if ($request->Sede != 0) {
    //             $query->where('CodigoSede', $request->Sede);
    //         }

    //         // Filtro por rango de fechas
    //         // if ($request->has('FechaInicio') && $request->has('FechaFin')) {
    //         //     $query->whereBetween('Fecha', [$request->FechaInicio, $request->FechaFin]);
    //         // }

    //         $horarios = $query->get();

    //         return response()->json($horarios, 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'msg' => 'Error al listar los horarios.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function registrarHorario(Request $request)
    {

        try {
            $validarHorario = DB::table('horario')
                ->where('CodigoMedico', $request->CodigoMedico)
                ->where('Vigente', 1)
                ->whereDate('Fecha', $request->Fecha)
                ->where(function ($query) use ($request) {
                    $query->whereTime('HoraInicio', '<', $request->HoraFin)
                        ->whereTime('HoraFin', '>', $request->HoraInicio);
                })
                ->first();


            if ($validarHorario) {
                Log::warning('Intento de registrar horario que se cruza con otro existente', [
                    'Controlador' => 'HorarioController',
                    'Metodo' => 'registrarHorario',
                    'CodigoMedico' => $request->CodigoMedico,
                    'Fecha' => $request->Fecha,
                    'HoraInicio' => $request->HoraInicio,
                    'HoraFin' => $request->HoraFin,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['msg' => 'El horario se cruza con otro ya registrado. Cruce: ' . $validarHorario->HoraInicio . ' - ' . $validarHorario->HoraFin], 500);
            }

            Horario::create($request->all());
            Log::info('Horario registrado correctamente', [
                'Controlador' => 'HorarioController',
                'Metodo' => 'registrarHorario',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'msg' => 'Horario registrado correctamente.'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar el horario', [
                'Controlador' => 'HorarioController',
                'Metodo' => 'registrarHorario',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'msg' => 'Error al registrar el horario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
