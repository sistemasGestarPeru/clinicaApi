<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Trabajador\RegistrarRequest;
use App\Http\Resources\Recaudacion\Trabajador\TrabajadorResource;
use App\Models\Personal\AsignacionSede;
use App\Models\Personal\ContratoLaboral;
use App\Models\Personal\Persona;
use App\Models\Personal\Trabajador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
                ->get();

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

            $resultados = DB::table('clinica_db.trabajadors as t')
                ->join('clinica_db.asignacion_sedes as ase', 'ase.CodigoTrabajador', '=', 't.Codigo')
                ->join('clinica_db.sedesrec as s', 's.Codigo', '=', 'ase.CodigoSede')
                ->join('clinica_db.empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
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
                                WHEN '$fecha' BETWEEN ase.FechaInicio AND IFNULL(ase.FechaFin, '$fecha') THEN 1 
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
        try {
            date_default_timezone_set('America/Lima');
            $fecha = date('Y-m-d'); // Obtener la fecha actual en formato Y-m-d

            $results = DB::table('contrato_laborals as cl')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select(
                    'cl.Codigo',
                    'e.Nombre',
                    'cl.CodigoEmpresa',
                    'cl.FechaInicio',
                    'cl.FechaFin',
                    DB::raw("CASE 
                                WHEN '$fecha' BETWEEN cl.FechaInicio AND IFNULL(cl.FechaFin, '$fecha') THEN 1 
                                ELSE 0 
                             END as VigenciaContrato")
                )
                ->where('cl.CodigoTrabajador', $codTrab)
                ->where('cl.Vigente', 1)
                ->where('e.Vigente', 1)
                ->orderBy('cl.FechaFin', 'desc')
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
                    'cl.Vigente'
                )
                ->where('cl.Codigo', $codContratoLab)
                ->get();

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

    public function regAsignacionSede(Request $request)
    {
        $asignacionData = $request->input('asignacionSede');

        try {

            AsignacionSede::create($asignacionData);

            return (['msg' => 'Sede Asignada correctamente']);
        } catch (\Exception $e) {

            return response()->json('Error al Asignar Sede', 400);
        }
    }

    public function regContratoLab(Request $request)
    {
        $contratoData = $request->input('contrato');

        try {

            ContratoLaboral::create($contratoData);

            return (['msg' => 'Contrato Laboral registrados correctamente']);
        } catch (\Exception $e) {

            return response()->json('Error al registrar al Contrato Laboral', 400);
        }
    }

    public function consultarContratoLab(Request $request)
    {
        $codigoTrabajador = $request->input('CodigoTrabajador');

        try {
            $contratoLab = DB::table('contrato_laborals as cl')
                ->Join('clinica_db.empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
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

        $trabajadorRules = [
            'CorreoCoorporativo' => [
                'required',
                'email',
                Rule::unique('trabajadors')->where(function ($query) {
                    return $query->where('vigente', 1);
                }),
            ],
        ];


        // Validate trabajador data
        $trabajadorValidator = Validator::make($trabajadorData, $trabajadorRules);
        if ($trabajadorValidator->fails()) {
            return response()->json(['errors' => $trabajadorValidator->errors()], 400);
        }


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
        $trabajadorData = $request->input('trabajador');
        $personaData = $request->input('persona');

        $trabajadorRules = [
            'CorreoCoorporativo' => [
                'required',
                'email',
                Rule::unique('trabajadors')->where(function ($query) {
                    return $query->where('vigente', 1);
                }),
            ],
        ];

        // Validate trabajador data
        $trabajadorValidator = Validator::make($trabajadorData, $trabajadorRules);
        if ($trabajadorValidator->fails()) {
            return response()->json(['errors' => $trabajadorValidator->errors()], 400);
        }

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


        $personaRules = [
            'NumeroDocumento' => [
                'required',
                'string',
                Rule::unique('personas')
                    ->where('CodigoTipoDocumento', $personaData['CodigoTipoDocumento'])
                    ->ignore($personaData['Codigo'], 'Codigo')
            ],
            'CodigoTipoDocumento' => 'required|integer',

        ];

        $trabajadorRules = [
            'CorreoCoorporativo' => [
                'required',
                'email',
                Rule::unique('trabajadors')->ignore($personaData['Codigo'], 'Codigo')
            ],
        ];

        // Validate persona data
        $personaValidator = Validator::make($personaData, $personaRules);
        if ($personaValidator->fails()) {
            return response()->json(['errors' => $personaValidator->errors()], 400);
        }

        // Validate trabajador data
        $trabajadorValidator = Validator::make($trabajadorData, $trabajadorRules);
        if ($trabajadorValidator->fails()) {
            return response()->json(['errors' => $trabajadorValidator->errors()], 400);
        }

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
            $personas = DB::table('clinica_db.personas as p')
                ->join('clinica_db.tipo_documentos as td', 'p.CodigoTipoDocumento', '=', 'td.Codigo')
                ->Join('clinica_db.trabajadors as t', 'p.Codigo', '=', 't.Codigo')
                ->select(
                    'p.Codigo',
                    'p.Nombres',
                    'p.Apellidos',
                    'p.NumeroDocumento',
                    'td.Siglas as DescTipoDocumento'
                )
                ->where('p.NumeroDocumento', 'like', '%' . $numDocumento . '%')
                ->where('p.Nombres', 'like', '%' . $nombre . '%')
                ->where('p.Vigente', '=', 1)
                ->where('t.Vigente', '=', 1)
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
                        'FechaNacimiento'
                    )
                    ->where('Codigo', $persona->Codigo)
                    ->where('Vigente', 1)
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
        $persona = DB::table('clinica_db.personas')
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
                'CodigoDepartamento'
            )
            ->where('Codigo', '=', $Codigo)
            ->where('Vigente', '=', 1)
            ->first();

        // Consultar los datos de trabajador
        $trabajador = DB::table('clinica_db.trabajadors')
            ->select(
                'CorreoCoorporativo',
                'FechaNacimiento',
                'Vigente'
            )
            ->where('Codigo', '=', $Codigo)
            ->where('Vigente', '=', 1)
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
