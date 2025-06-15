<?php

namespace App\Http\Controllers\API\MedioPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recaudacion\MedioPago;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedioPagoController extends Controller
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

    public function listarMedioPago()
    {
        try {
            $entidad = MedioPago::all();
            Log::info('Listar Medios Pago', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'listarMedioPago',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Medios Pago', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'listarMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarMedioPago(Request $request)
    {
        if (MedioPago::where('CodigoSUNAT', $request->CodigoSUNAT)->exists()) {

            //log warning
            Log::warning('Intento de registrar un medio de pago con código SUNAT duplicado', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarMedioPago',
                'CodigoSUNAT' => $request->CodigoSUNAT,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'error' => 'El Código SUNAT ya existe.'
            ], 500);
        }

        try {
            // Intentar registrar el nuevo medio de pago
            MedioPago::create($request->all());
            Log::info('Medio de Pago registrado correctamente', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarMedioPago',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Medio de Pago registrado correctamente'], 201);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                Log::warning('Intento de registrar un medio de pago con nombre duplicado', [
                    'Controlador' => 'MedioPagoController',
                    'Metodo' => 'registrarMedioPago',
                    'Nombre' => $request->Nombre,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'error' => 'El medio de pago ya existe. Intente con otro nombre.'
                ], 500);
            }
            Log::error('Error al registrar Medio de Pago', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Error en la base de datos. Inténtelo nuevamente.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error inesperado al registrar Medio de Pago', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function consultarMedioPago($codigo)
    {
        try {
            $entidad = MedioPago::find($codigo);
            if (!$entidad) {
                Log::warning('Medio de Pago no encontrado', [
                    'Controlador' => 'MedioPagoController',
                    'Metodo' => 'consultarMedioPago',
                    'Codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Medio de Pago no encontrado'], 404);
            }
            Log::info('Medio de Pago consultado correctamente', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'consultarMedioPago',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Medio de Pago', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'consultarMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarMedioPago(Request $request)
    {
        try {
            $entidad = MedioPago::find($request->Codigo);
            $entidad->update($request->all());
            Log::info('Medio de Pago actualizado correctamente', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'actualizarMedioPago',
                'Codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Medio de Pago actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Medio de Pago', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'actualizarMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //Local Medio Pago

    public function mediosPagoDisponible($sede)
    {
        try {
            $mediosPago = DB::table('mediopago')
                ->whereNotIn('codigo', function ($query) use ($sede) {
                    $query->select('CodigoMedioPago')
                        ->from('localmediopago')
                        ->where('CodigoSede', $sede);
                })
                ->get();
            Log::info('Medios de Pago disponibles consultados', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'mediosPagoDisponible',
                'Sede' => $sede,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($mediosPago, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Medios de Pago disponibles', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'mediosPagoDisponible',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarLocalMedioPago($sede)
    {
        try {
            $mediosPago = DB::table('localmediopago as lmp')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'lmp.CodigoMedioPago')
                ->join('sedesrec as s', 's.Codigo', '=', 'lmp.CodigoSede')
                ->where('lmp.CodigoSede', $sede)
                ->select('lmp.Codigo', 'mp.Nombre', 'lmp.Vigente', 's.Nombre as Sede')
                ->get();

            Log::info('Listado de Medios de Pago locales consultados', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'listarLocalMedioPago',
                'Sede' => $sede,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($mediosPago, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Medios de Pago locales', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'listarLocalMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarLocalMedioPago(Request $request)
    {
        try {
            DB::table('localmediopago')->insert([
                'CodigoSede' => $request->CodigoSede,
                'CodigoMedioPago' => $request->CodigoMedioPago
            ]);
            Log::info('Medio de Pago local registrado correctamente', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarLocalMedioPago',
                'CodigoSede' => $request->CodigoSede,
                'CodigoMedioPago' => $request->CodigoMedioPago,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Medio de Pago registrado correctamente'], 201);
        } catch (\Exception $e) {
            Log::error('Error al registrar Medio de Pago local', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'registrarLocalMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarLocalMedioPago(Request $request)
    {
        try {
            DB::table('localmediopago')
                ->where('Codigo', $request->Codigo)
                ->update([
                    'CodigoMedioPago' => $request->CodigoMedioPago,
                    'Vigente' => $request->Vigente
                ]);
            Log::info('Medio de Pago local actualizado correctamente', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'actualizarLocalMedioPago',
                'Codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Medio de Pago actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar Medio de Pago local', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'actualizarLocalMedioPago',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarMedioPagoLocal($codigo)
    {
        try {
            $entidad = DB::table('localmediopago')
                ->where('Codigo', $codigo)
                ->select('Codigo', 'CodigoMedioPago', 'Vigente')
                ->first();
            if (!$entidad) {
                Log::warning('Medio de Pago local no encontrado', [
                    'Controlador' => 'MedioPagoController',
                    'Metodo' => 'consultarMedioPagoLocal',
                    'Codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Medio de Pago local no encontrado'], 404);
            }
            Log::info('Medio de Pago local consultado correctamente', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'consultarMedioPagoLocal',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Medio de Pago local', [
                'Controlador' => 'MedioPagoController',
                'Metodo' => 'consultarMedioPagoLocal',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
