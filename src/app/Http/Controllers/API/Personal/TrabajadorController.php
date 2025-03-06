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
            return (['msg' => 'Sede Asignada correctamente']);
        } catch (\Exception $e) {
            return response()->json('Error al Asignar Sede', 400);
        }
    }

    public function regContratoLab(RecaudacionGuardarContratoLaboralRequest $request)
    {
        try {
            new (ContratoLaboral::create($request->all()));
            return (['msg' => 'Contrato Laboral registrado correctamente']);
        } catch (\Exception $e) {

            return response()->json('Error al registrar al Contrato Laboral', 400);
        }
    }

    public function actualizarAsignacion(Request $request)
    {
        $asignacionData = $request->input('asignacionSede');
        try {
            $asignacion = AsignacionSede::find($asignacionData['Codigo']);
            $asignacion->update($asignacionData);
            return response()->json(['msg' => 'Asignación actualizada correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la Asignación: ' . $e->getMessage()], 400);
        }
    }

    public function actualizarContrato(Request $request)
    {
        $contratoData = $request->input('contrato');
        try {
            $contrato = ContratoLaboral::find($contratoData['Codigo']);
            $contrato->update($contratoData);
            return response()->json(['msg' => 'Contrato actualizado correctamente'], 200);
        } catch (\Exception $e) {
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

            return response()->json($asignacion);
        } catch (\Exception $e) {

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
                ->where('ase.Vigente', 1)
                ->where('e.Vigente', 1)
                ->where('e.Codigo', $codEmpresa)
                ->select(
                    'ase.Codigo',
                    's.Nombre',
                    'ase.FechaInicio',
                    'ase.FechaFin',
                    'ase.Vigente',
                    DB::raw("CASE 
                                WHEN  ase.FechaFin IS NULL OR {$fecha} < ase.FechaFin THEN 1
                                ELSE 0 
                             END as EstadoAsignacion")
                )
                ->get();

            return response()->json($resultados);
        } catch (\Exception $e) {
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
                    DB::raw("CASE 
                                WHEN cl.FechaFin IS NULL OR {$fecha} < cl.FechaFin THEN 1
                                ELSE 0 
                             END AS VigenciaContrato")
                )
                ->where('cl.CodigoTrabajador', $codTrab)
                ->where('cl.Vigente', 1)
                ->where('e.Vigente', 1)
                ->orderBy('VigenciaContrato', 'desc')
                ->get();
    
            return response()->json($results, 200);
        } catch (\Exception $e) {
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

            return response()->json($results, 200);
        } catch (\Exception $e) {

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

            return response()->json($contratoLab, 200);
        } catch (\Exception $e) {
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

            return response()->json(['msg' => 'Trabajador registrado correctamente: ' . $trabajadorData['Codigo']], 200);
        } catch (\Exception $e) {
            DB::rollBack();
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
            return response()->json(['msg' => 'Trabajador registrado correctamente: ' . $trabajadorData['Codigo']], 200);
        } catch (\Exception $e) {
            DB::rollBack();
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
            $persona = Persona::find($personaData['Codigo']);
            $persona->update($personaData);

            $trabajador = Trabajador::find($personaData['Codigo']);
            $trabajador->update($trabajadorData);

            DB::commit();
            return response()->json(['msg' => 'Trabajador actualizado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar al Trabajador: ' . $e->getMessage()], 400);
        }
    }

    public function buscar(Request $request)
    {
        $numDocumento = $request->input('numDocumento', '');
        $nombre = $request->input('nombre', '');

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
                    't.Vigente as VigenteTrabajador'
                )
                ->where('p.NumeroDocumento', 'like', '%' . $numDocumento . '%')
                ->where('p.Nombres', 'like', '%' . $nombre . '%')
                ->where('p.Vigente', '=', 1)
                ->get();

            return response()->json($personas, 200);
        } catch (\Exception $e) {
            return response()->json('Error al obtener los datos', 400);
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
                    return response()->json([
                        'persona' => $persona,
                        'trabajador' => $trabajador
                    ], 200);
                } else {
                    return response()->json([
                        'persona' => $persona
                    ], 200);
                }
            } else {
                return response()->json(['mensaje' => 'No se encontraron registros', 'resp' => -1], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los datos: ' . $e->getMessage()], 400);
        }
    }


    public function consultarTrabCodigo(Request $request)
    {
        $Codigo = $request->input('Codigo');

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

            return response()->json($resultado);
        } else {
            return response()->json(['error' => 'No se encontraron registros'], 404);
        }
    }
}
