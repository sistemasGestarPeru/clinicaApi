<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\AsignacionSede\RegistrarAsignacionSedeRequest;
use App\Http\Requests\Recaudacion\Trabajador\RegistrarRequest as RegistrarTrabajadorRequest;
use App\Http\Requests\Recaudacion\Cliente\ActualizarRequest as ActualizarPersonaRequest;
use App\Http\Requests\Recaudacion\Cliente\RegistrarRequest as RegistrarPersonaRequest;
use App\Http\Resources\Recaudacion\Trabajador\TrabajadorResource;
use App\Models\Personal\AsignacionSede;
use App\Models\Personal\ContratoLaboral;
use App\Models\Personal\Persona;
use App\Models\Personal\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\Recaudacion\ContratoLaboral\GuardarContratoLaboralRequest as RecaudacionGuardarContratoLaboralRequest;

class TrabajadorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

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

    public function regAsignacionSede(RegistrarAsignacionSedeRequest $request)
    {
        try {
            new (AsignacionSede::create($request->all()));

            //log info
            Log::info('Registrar Asignación Sede', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'regAsignacionSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'datos' => $request->all()
            ]);
            return (['msg' => 'Sede Asignada correctamente']);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al Asignar Sede', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'regAsignacionSede',
                'datos' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json('Error al Asignar Sede', 400);
        }
    }

    public function regContratoLab(RecaudacionGuardarContratoLaboralRequest $request)
    {
        try {
            new (ContratoLaboral::create($request->all()));

            //log info
            Log::info('Registrar Contrato Laboral', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'regContratoLab',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'datos' => $request->all()
            ]);
            return (['msg' => 'Contrato Laboral registrado correctamente']);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al registrar Contrato Laboral', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'regContratoLab',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'datos' => $request->all()
            ]);
            return response()->json('Error al registrar al Contrato Laboral', 400);
        }
    }

    public function actualizarAsignacion(Request $request)
    {
        $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

        $asignacionData = $request->input('asignacionSede');
        try {
            //Verificar
            $asignacion = AsignacionSede::find($asignacionData['Codigo']);
            $estadoActual = $asignacion->Vigente;

            $asignacionVigente = $asignacion->FechaFin == null || $asignacion->FechaFin > $fecha;

            if ($estadoActual == 0) {

                //log warning
                Log::warning('Intento de actualización de asignación inactiva', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'actualizarAsignacion',
                    'CodigoAsignacion' => $asignacionData['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'No se puede actualizar una asignación Inactiva.'], 400);
            }
            if (!$asignacionVigente) {

                //log warning
                Log::warning('Intento de actualización de asignación finalizada', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'actualizarAsignacion',
                    'CodigoAsignacion' => $asignacionData['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'No se puede actualizar una asignación Finalizada.'], 400);
            }

            if ($estadoActual == 1 && $asignacionVigente) {
                $asignacion->update($asignacionData);
            }

            //log info
            Log::info('Asignación actualizada correctamente', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'actualizarAsignacion',
                'CodigoAsignacion' => $asignacionData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['msg' => 'Asignación actualizada correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar Asignación', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'actualizarAsignacion',
                'CodigoAsignacion' => $asignacionData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'datos' => $request->all()
            ]);

            return response()->json(['error' => 'Error al actualizar la Asignación', 'bd' => $e->getMessage()], 400);
        }
    }

    public function actualizarContrato(Request $request)
    {
        $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

        $contratoData = $request->input('contrato');
        $trabajador = $request->input('trabajador');

        DB::beginTransaction();
        try {
            $contrato = ContratoLaboral::find($contratoData['Codigo']);
            $estadoActual = $contrato->Vigente;
            //Verificar
            // Verificar si el contrato está vigente antes de actualizarlo
            $contratoVigente = $contrato->FechaFin == null || $contrato->FechaFin > $fecha;

            if ($estadoActual == 0) {

                //log warning
                Log::warning('Intento de actualización de contrato inactivo', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'actualizarContrato',
                    'CodigoContrato' => $contratoData['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'No se puede actualizar un contrato Inactivo.'], 400);
            }

            if (!$contratoVigente) {

                //log warning
                Log::warning('Intento de actualización de contrato finalizado', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'actualizarContrato',
                    'CodigoContrato' => $contratoData['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'No se puede actualizar un contrato Finalizado.'], 400);
            }

            if ($estadoActual == 1 && $contratoVigente) {

                $resultados = DB::table('trabajadors as t')
                    ->join('asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
                    ->join('sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
                    ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                    ->where('t.Codigo', $trabajador)
                    ->where('s.Vigente', 1)
                    ->where('ase.Vigente', 1)
                    ->where(function ($query) use ($fecha) {
                        $query->whereNull('ase.FechaFin')
                            ->orWhere('ase.FechaFin', '>=', $fecha);
                    })
                    ->where('e.Codigo', $contratoData['CodigoEmpresa'])
                    ->select('ase.Codigo', 'ase.FechaInicio', 'ase.FechaFin')
                    ->get();

                // validar que las fechas $contratoData['FechaInicio'] y $contratoData['FechaFin'] no se superpongan con las fechas de las asignaciones existentes
                foreach ($resultados as $resultado) {
                    if (($contratoData['FechaInicio'] >= $resultado->FechaInicio && $contratoData['FechaInicio'] <= $resultado->FechaFin) ||
                        ($contratoData['FechaFin'] >= $resultado->FechaInicio && $contratoData['FechaFin'] <= $resultado->FechaFin)
                    ) {

                        //log warning
                        Log::warning('Fechas de contrato superpuestas', [
                            'Controlador' => 'TrabajadorController',
                            'Metodo' => 'actualizarContrato',
                            'CodigoContrato' => $contratoData['Codigo'],
                            'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                        ]);

                        return response()->json(['error' => 'Las fechas del contrato se superponen con las fechas de la asignación existente.'], 400);
                    }
                }

                $contrato->update($contratoData);
            }

            if ($contratoData['Vigente'] == 0) {
                $resultados = DB::table('trabajadors as t')
                    ->join('asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
                    ->join('sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
                    ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                    ->where('t.Codigo', $trabajador)
                    ->where('s.Vigente', 1)
                    ->where('ase.Vigente', 1)
                    ->where('e.Codigo', $contratoData['CodigoEmpresa'])
                    ->select('ase.Codigo')
                    ->get();
                // actualizar los vigente = 0 de la tabla asignacion_sedes
                foreach ($resultados as $resultado) {
                    $asignacion = AsignacionSede::find($resultado->Codigo);
                    $asignacion->update(['Vigente' => 0]);
                }
            }

            DB::commit();

            //log info
            Log::info('Contrato actualizado correctamente', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'actualizarContrato',
                'CodigoContrato' => $contratoData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['msg' => 'Contrato actualizado correctamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al actualizar Contrato', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'actualizarContrato',
                'CodigoContrato' => $contratoData['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'datos' => $request->all()
            ]);

            return response()->json(['error' => 'Error al actualizar al Contrato: ' . $e->getMessage()], 400);
        }
    }

    public function consultarAsignacion($codAsignacion)
    {
        try {
            $asignacion = DB::table('asignacion_sedes as ass')
                ->join('sedesrec as s', 's.Codigo', '=', 'ass.CodigoSede')
                ->select(
                    'ass.Codigo',
                    'ass.CodigoSede',
                    's.Nombre',
                    's.CodigoEmpresa',
                    'ass.FechaInicio',
                    'ass.FechaFin',
                    'ass.Vigente'
                )
                ->where('ass.Codigo', $codAsignacion)
                ->first();

            //log info

            Log::info('Consultar Asignacion', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarAsignacion',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $codAsignacion
            ]);

            return response()->json($asignacion);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Asignacion', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarAsignacion',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $codAsignacion,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json('Error al obtener datos', 400);
        }
    }

    public function listarAsignaciones($codTrab, $codEmpresa)
    {
        try {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

            $resultados = DB::table('trabajadors as t')
                ->join('asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
                ->join('sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->where('t.Codigo', $codTrab)
                ->where('s.Vigente', 1)
                // ->where('ase.Vigente', 1)
                ->where('e.Vigente', 1)
                ->where('e.Codigo', $codEmpresa)
                ->select(
                    'ase.Codigo',
                    's.Nombre',
                    'ase.FechaInicio',
                    'ase.FechaFin',
                    'ase.Vigente',
                    DB::raw("CASE 
                                WHEN ase.FechaFin IS NULL OR ase.FechaFin >= ? THEN 1 
                                ELSE 0 
                            END as EstadoAsignacion")
                )
                ->orderBy('EstadoAsignacion', 'desc')
                ->addBinding([$fecha], 'select') // Bind de la fecha
                ->get();

            //log info
            Log::info('Listar Asignaciones', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'listarAsignaciones',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $resultados->count(),
                'CodigoTrabajador' => $codTrab,
                'CodigoEmpresa' => $codEmpresa
            ]);

            return response()->json($resultados);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar Asignaciones', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'listarAsignaciones',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'CodigoTrabajador' => $codTrab,
                'CodigoEmpresa' => $codEmpresa
            ]);

            return response()->json('Error al obtener datos', 400);
        }
    }


    public function listarContratos($codTrab)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d
        try {
            $results = DB::table('contrato_laborals as cl')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select(
                    'cl.Codigo',
                    'e.Nombre',
                    'cl.CodigoEmpresa',
                    'cl.FechaInicio',
                    'cl.FechaFin',
                    'cl.Vigente',
                    DB::raw("CASE 
                            WHEN cl.FechaFin IS NULL OR cl.FechaFin >= ? THEN 1
                            ELSE 0 
                         END AS VigenciaContrato")
                )
                ->where('cl.CodigoTrabajador', $codTrab)
                // ->where('cl.Vigente', 1)
                ->where('e.Vigente', 1)
                ->orderBy('VigenciaContrato', 'desc')
                ->addBinding([$fecha], 'select') // Bind de la fecha
                ->get();

            //log info
            Log::info('Listar Contratos', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'listarContratos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $results->count(),
                'CodigoTrabajador' => $codTrab
            ]);

            return response()->json($results, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar Contratos', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'listarContratos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'CodigoTrabajador' => $codTrab
            ]);

            return response()->json('Error al obtener datos', 400);
        }
    }



    public function consultarContrato($codContratoLab)
    {
        try {

            $results = DB::table('contrato_laborals as cl')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select(
                    'cl.Codigo',
                    'e.Nombre',
                    'cl.CodigoEmpresa',
                    'cl.FechaInicio',
                    'cl.FechaFin',
                    'cl.Tipo',
                    'cl.Tiempo',
                    'cl.SueldoBase',
                    'cl.Vigente'
                )
                ->where('cl.Codigo', $codContratoLab)
                ->first();

            //log info
            Log::info('Consultar Contrato', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarContrato',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoContrato' => $codContratoLab
            ]);

            return response()->json($results, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Contrato', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarContrato',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoContrato' => $codContratoLab,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json('Error al obtener datos', 400);
        }
    }
    // public function listarContratosAsignacion($codTrab)
    // {
    //     try {
    //         $results = DB::table('empresas as e')
    //             ->join('sedesrec as s', 's.CodigoEmpresa', '=', 'e.Codigo')
    //             ->join('contrato_laborals as cl', 'cl.CodigoEmpresa', '=', 'e.Codigo')
    //             ->leftJoin('asignacion_sedes as ass', 'ass.CodigoSede', '=', 's.Codigo')
    //             ->select(
    //                 'e.Nombre as NombreEmpresa',
    //                 's.Nombre as SedeEmpresa',
    //                 'ass.CodigoSede',
    //                 'cl.Vigente as CLVigente',
    //                 'ass.Vigente as assVigente'
    //             )
    //             ->where('cl.CodigoTrabajador', $codTrab)
    //             ->orderBy('e.Nombre')
    //             ->get();

    //         return response()->json($results, 200);
    //     } catch (\Exception $e) {

    //         return response()->json('Error al obtener datos', 400);
    //     }
    // }



    public function consultarContratoLab(Request $request)
    {
        $codigoTrabajador = $request->input('CodigoTrabajador');

        try {
            $contratoLab = DB::table('contrato_laborals as cl')
                ->Join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select(
                    'cl.Codigo',
                    'e.Nombre',
                )
                ->where('cl.CodigoTrabajador', '=', $codigoTrabajador)
                ->where('cl.Vigente', '=', 1)
                ->get();

            //log info
            Log::info('Consultar Contrato Laboral', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarContratoLab',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoTrabajador' => $codigoTrabajador
            ]);

            return response()->json($contratoLab, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Contrato Laboral', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarContratoLab',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoTrabajador' => $codigoTrabajador,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json('Error al obtener los datos', 400);
        }
    }


    public function registrarPersonaTrabajador(Request $request)
    {
        $trabajadorData = $request->input('trabajador');
        $personaData = $request->input('persona');
        $personaData['Vigente'] = 1;
        // Validar persona
        $personaValidator = Validator::make($personaData, (new RegistrarPersonaRequest())->rules());
        $personaValidator->validate();

        // Validar trabajador
        $trabajadorValidator = Validator::make($trabajadorData, (new RegistrarTrabajadorRequest())->rules());
        $trabajadorValidator->validate();

        DB::beginTransaction();
        try {
            // Crear la persona y obtener el ID del último registro insertado
            $persona = Persona::create($personaData);

            // Usar el ID de la persona recién creada
            $trabajadorData['Codigo'] = $persona->Codigo; // O el nombre de la clave primaria

            // Crear el trabajador con el Código de la persona
            Trabajador::create($trabajadorData);

            DB::commit();

            //log info

            Log::info('Registrar Trabajador', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'registrarPersonaTrabajador',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoTrabajador' => $trabajadorData['Codigo'],
                'datos' => $request->all()
            ]);

            return response()->json(['msg' => 'Trabajador registrado correctamente: ' . $trabajadorData['Codigo']], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al registrar Trabajador', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'registrarPersonaTrabajador',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'datos' => $request->all()
            ]);

            return response()->json(['error' => 'Error al registrar al Trabajador: ' . $e->getMessage()], 400);
        }
    }

    public function registrarTrabajador(Request $request)
    {

        // Extraer datos del request
        $personaData = $request->input('persona');
        $trabajadorData = $request->input('trabajador');
        $personaData['Vigente'] = 1;

        // Validar persona
        $personaValidator = Validator::make($personaData, (new ActualizarPersonaRequest())->rules());
        $personaValidator->validate();

        // Validar trabajador
        $trabajadorValidator = Validator::make($trabajadorData, (new RegistrarTrabajadorRequest())->rules());
        $trabajadorValidator->validate();

        DB::beginTransaction();
        try {
            $persona = Persona::find($personaData['Codigo']);
            $persona->update($personaData);

            $trabajadorData['Codigo'] = $personaData['Codigo'];
            Trabajador::create($trabajadorData);
            DB::commit();

            //log info
            Log::info('Registrar Trabajador', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'registrarTrabajador',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoTrabajador' => $trabajadorData['Codigo'],
                'datos' => $request->all()
            ]);

            return response()->json(['msg' => 'Trabajador registrado correctamente: ' . $trabajadorData['Codigo']], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            //log error
            Log::error('Error al registrar Trabajador', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'registrarTrabajador',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'datos' => $request->all()
            ]);
            return response()->json(['error' => 'Error al registrar al Trabajador: ' . $e->getMessage()], 400);
        }
    }

    public function actualizarTrabajador(Request $request)
    {
        $trabajadorData = $request->input('trabajador');
        $personaData = $request->input('persona');
        $personaData['Vigente'] = 1;
        // Validar persona
        $personaValidator = Validator::make($personaData, (new ActualizarPersonaRequest())->rules());
        $personaValidator->validate();

        // Validar trabajador con la regla de 'ignore' actualizada
        $trabajadorRules = (new RegistrarTrabajadorRequest())->rules();

        // Modificar la regla de 'CorreoCoorporativo' para usar 'Codigo' en la validación
        $trabajadorRules['CorreoCoorporativo'][2] = Rule::unique('trabajadors', 'CorreoCoorporativo')
            ->where('Vigente', 1)
            ->ignore($personaData['Codigo'], 'Codigo');

        // Validamos los datos de trabajador con las reglas modificadas
        $trabajadorValidator = Validator::make($trabajadorData, $trabajadorRules);
        $trabajadorValidator->validate();

        DB::beginTransaction();
        try {
            $personaRegistrado = Persona::find($personaData['Codigo']);
            $trabajadorRegistrado = Trabajador::find($personaData['Codigo']);

            // Verificar si la persona existe
            if (!$personaRegistrado) {

                //log warning
                Log::warning('Intento de actualización de persona no encontrada', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'actualizarTrabajador',
                    'CodigoPersona' => $personaData['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'Trabajador no encontrado.'], 404);
            }
            // Verificar si el trabajador existe
            if (!$trabajadorRegistrado) {

                //log warning
                Log::warning('Intento de actualización de trabajador no encontrado', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'actualizarTrabajador',
                    'CodigoTrabajador' => $trabajadorData['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['error' => 'Trabajador no encontrado.'], 404);
            }

            $personaRegistrado->update($personaData);
            $trabajadorRegistrado->update($trabajadorData);

            // Si trabajadorData tiene Vigente = 0 entonces damos de baja a todos sus usuarios, contratos y asignaciones vigentes = 1
            if ($trabajadorData['Vigente'] == 0) {
                // Actualizar los contratos laborales vigentes a 0
                DB::table('contrato_laborals')
                    ->where('CodigoTrabajador', $trabajadorRegistrado->Codigo)
                    ->where('Vigente', 1)
                    ->update(['Vigente' => 0]);

                // Actualizar las asignaciones vigentes a 0
                DB::table('asignacion_sedes')
                    ->where('CodigoTrabajador', $trabajadorRegistrado->Codigo)
                    ->where('Vigente', 1)
                    ->update(['Vigente' => 0]);

                DB::table('users')
                    ->where('CodigoPersona', $trabajadorRegistrado->Codigo)
                    ->where('Vigente', 1)
                    ->update(['Vigente' => 0]);
            }

            DB::commit();

            //log info
            Log::info('Actualizar Trabajador', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'actualizarTrabajador',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'CodigoTrabajador' => $trabajadorData['Codigo'],
                'datos' => $request->all()
            ]);

            return response()->json(['msg' => 'Trabajador actualizado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar Trabajador', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'actualizarTrabajador',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'datos' => $request->all()
            ]);
            return response()->json(['error' => 'Error al actualizar al Trabajador: ' . $e->getMessage()], 400);
        }
    }

    public function buscar(Request $request)
    {
        $numDocumento = $request->input('numDocumento', '');
        $nombre = $request->input('nombre', '');
        $tipo = $request->input('tipo', '');

        try {
            $personas = DB::table('personas as p')
                ->join('tipo_documentos as td', 'p.CodigoTipoDocumento', '=', 'td.Codigo')
                ->Join('trabajadors as t', 'p.Codigo', '=', 't.Codigo')
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.NumeroDocumento',
                    'td.Siglas as DescTipoDocumento',
                    't.Vigente as VigenteTrabajador',
                    't.Tipo',
                    't.Vigente as tVigente',
                    'p.Vigente as pVigente'
                )
                ->where('p.NumeroDocumento', 'like', $numDocumento . '%')
                ->where('p.Nombres', 'like', $nombre . '%')
                ->where('t.Tipo', 'like', $tipo . '%')
                // ->where('p.Vigente', '=', 1)
                ->orderBy('p.Nombres', 'asc')
                ->get();

            //log info
            Log::info('Buscar Personas', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'buscar',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'query' => $request->all(),
                'CantidadResultados' => $personas->count()
            ]);

            return response()->json($personas, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al buscar Personas', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'buscar',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'query' => $request->all()
            ]);

            return response()->json('Error al obtener los datos', 400);
        }
    }

    public function listarTrabajadores()
    {
        try {
            $trabajadores = DB::table('trabajadors as t')
                ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('users as u')
                        ->whereRaw('u.CodigoPersona = t.Codigo');
                })
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->select([
                    't.Codigo',
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) AS NombreCompleto"),
                    't.CorreoCoorporativo',
                    'p.NumeroDocumento',
                    DB::raw("LOWER(CONCAT(LEFT(p.Nombres, 1), 
                                        SUBSTRING_INDEX(p.Apellidos, ' ', 1), 
                                        LEFT(SUBSTRING_INDEX(p.Apellidos, ' ', -1), 1)
                    )) AS NombreUsuario")
                ])
                ->get();

            //log info
            Log::info('Listar Trabajadores', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'listarTrabajadores',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $trabajadores->count()
            ]);

            return response()->json($trabajadores, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Trabajadores', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'listarTrabajadores',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Error al obtener los trabajadores'], 400);
        }
    }


    public function consultarNumDoc(Request $request)
    {
        $numDocumento = $request->input('numDocumento');
        $tipo = $request->input('tipo');

        try {
            $persona = DB::table('personas as p')
                ->leftJoin('trabajadors as t', 'p.Codigo', '=', 't.Codigo')
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.Direccion',
                    'p.Celular',
                    'p.Correo',
                    'p.CodigoNacionalidad',
                )
                ->where('p.NumeroDocumento', $numDocumento)
                ->where('p.CodigoTipoDocumento', $tipo)
                ->where('p.Vigente', 1)
                ->first();

            if ($persona) {
                $trabajador = DB::table('trabajadors')
                    ->select(
                        'CorreoCoorporativo',
                        'FechaNacimiento',
                        'CodigoSistemaPensiones',
                        'AutorizaDescuento',
                        'Vigente'
                    )
                    ->where('Codigo', $persona->Codigo)

                    ->first();

                if ($persona && $trabajador) {

                    //log info
                    Log::info('Consultar Persona y Trabajador', [
                        'Controlador' => 'TrabajadorController',
                        'Metodo' => 'consultarNumDoc',
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                        'query' => $request->all(),
                        'tipo' => $tipo
                    ]);

                    return response()->json([
                        'persona' => $persona,
                        'trabajador' => $trabajador
                    ], 200);
                } else {

                    //log info
                    Log::info('Consultar Persona sin Trabajador', [
                        'Controlador' => 'TrabajadorController',
                        'Metodo' => 'consultarNumDoc',
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                        'query' => $request->all(),
                    ]);

                    return response()->json([
                        'persona' => $persona
                    ], 200);
                }
            } else {

                //log info
                Log::info('Consultar Persona no encontrada', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'consultarNumDoc',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'query' => $request->all()
                ]);

                return response()->json(['mensaje' => 'No se encontraron registros', 'resp' => -1], 200);
            }
        } catch (\Exception $e) {
            Log::error('Error al consultar persona por documento', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarNumDoc',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'query' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Error al obtener los datos: ' . $e->getMessage()], 400);
        }
    }


    public function consultarTrabCodigo(Request $request)
    {
        $Codigo = $request->input('Codigo');

        try {
            // Consultar los datos de persona
            $persona = DB::table('personas')
                ->select(
                    'Codigo',
                    'Nombres',
                    'Apellidos',
                    'Direccion',
                    'Celular',
                    'Correo',
                    'NumeroDocumento',
                    'CodigoTipoDocumento',
                    'CodigoNacionalidad',
                    'CodigoDepartamento',

                )
                ->where('Codigo', '=', $Codigo)
                ->where('Vigente', '=', 1)
                ->first();

            // Consultar los datos de trabajador
            $trabajador = DB::table('trabajadors')
                ->select(
                    'CorreoCoorporativo',
                    'FechaNacimiento',
                    'CodigoSistemaPensiones',
                    'AutorizaDescuento',
                    'Vigente',
                    'Tipo'
                )
                ->where('Codigo', '=', $Codigo)

                ->first();

            if ($persona && $trabajador) {

                // Combinar ambos resultados en una única estructura
                $resultado = [
                    'persona' => $persona,
                    'trabajador' => $trabajador
                ];

                //log info
                Log::info('Consultar Persona y Trabajador por Código', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'consultarTrabCodigo',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'Codigo' => $Codigo
                ]);

                return response()->json($resultado);
            } else {
                //log info
                Log::info('Consultar Persona y Trabajador por Código no encontrados', [
                    'Controlador' => 'TrabajadorController',
                    'Metodo' => 'consultarTrabCodigo',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'Codigo' => $Codigo
                ]);
                return response()->json(['error' => 'No se encontraron registros'], 404);
            }
        } catch (\Exception $e) {
            //log error
            Log::error('Error al consultar persona y trabajador por Código', [
                'Controlador' => 'TrabajadorController',
                'Metodo' => 'consultarTrabCodigo',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Error al obtener los datos: ' . $e->getMessage()], 400);
        }
    }
}
