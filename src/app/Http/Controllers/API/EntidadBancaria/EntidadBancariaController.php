<?php

namespace App\Http\Controllers\API\EntidadBancaria;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\CuentaBancaria;
use App\Models\Recaudacion\EntidadBancaria;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntidadBancariaController extends Controller
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

    public function listarEntidadBancaria()
    {
        try {
            $entidad = EntidadBancaria::all();

            Log::info('Sedes listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarSedesEmpresas',
                'Cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar las entidades bancarias', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'listarEntidadBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarEntidadBancaria(Request $request)
    {
        try {
            EntidadBancaria::create($request->all());
            Log::info('Entidad Bancaria registrada correctamente', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'registrarEntidadBancaria',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Entidad Bancaria registrada correctamente'], 200);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {
                // log
                Log::warning('Error al registrar la entidad bancaria: Clave duplicada', [
                    'Controlador' => 'EntidadBancariaController',
                    'Metodo' => 'registrarEntidadBancaria',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json([
                    'error' => 'Las siglas de la Entidad Bancaria ya existen.'
                ], 500);
            }

            Log::error('Error al registrar la entidad bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'registrarEntidadBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error al registar la Entidad Bancaria.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error al registrar la entidad bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'registrarEntidadBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Ocurrió un error al registar Entidad Bancaria', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarEntidadBancaria($codigo)
    {
        try {
            $entidad = EntidadBancaria::find($codigo);
            if (!$entidad) {
                // log warning
                Log::warning('Entidad Bancaria no encontrada', [
                    'Controlador' => 'EntidadBancariaController',
                    'Metodo' => 'consultarEntidadBancaria',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Entidad Bancaria no encontrada'], 404);
            }

            Log::info('Entidad Bancaria consultada correctamente', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'consultarEntidadBancaria',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar la entidad bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'consultarEntidadBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEntidadBancaria(Request $request)
    {
        try {
            $entidad = EntidadBancaria::find($request->Codigo);
            if (!$entidad) {
                Log::warning('Entidad Bancaria no encontrada para actualizar', [
                    'Controlador' => 'EntidadBancariaController',
                    'Metodo' => 'actualizarEntidadBancaria',
                    'codigo' => $request->Codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Entidad Bancaria no encontrada'], 404);
            }
            $entidad->update($request->all());
            Log::info('Entidad Bancaria actualizada correctamente', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'actualizarEntidadBancaria',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Entidad Bancaria actualizada correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar la entidad bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'actualizarEntidadBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    // LOCAL EMPRESA CUENTA BANCARIA

    public function cuentaBancariaEmpresa($empresa)
    {
        try {
            $cuentasBancarias = DB::table('cuentabancaria as cb')
                ->join('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria')
                ->join('tipomoneda as tm', 'tm.Codigo', '=', 'cb.CodigoTipoMoneda')
                ->join('empresas as e', 'e.Codigo', '=', 'cb.CodigoEmpresa')
                ->select(
                    'cb.Codigo',
                    'cb.Numero',
                    'cb.CCI',
                    'tm.Nombre as NombreMoneda',
                    'eb.Nombre as NombreBanco',
                    'e.Nombre as NombreEmpresa',
                    'cb.Detraccion'
                )
                ->where('e.Codigo', $empresa)
                ->get();

            Log::info('Cuentas bancarias de la empresa consultadas correctamente', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'cuentaBancariaEmpresa',
                'empresa' => $empresa,
                'Cantidad' => count($cuentasBancarias),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($cuentasBancarias, 200);
        } catch (\Exception $e) {

            Log::error('Error al consultar las cuentas bancarias de la empresa', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'cuentaBancariaEmpresa',
                'empresa' => $empresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarCuentaBancaria($codigo)
    {
        try {
            $cuentaBancaria = CuentaBancaria::find($codigo);
            if (!$cuentaBancaria) {
                Log::warning('Cuenta Bancaria no encontrada', [
                    'Controlador' => 'EntidadBancariaController',
                    'Metodo' => 'consultarCuentaBancaria',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Cuenta Bancaria no encontrada'], 404);
            }
            return response()->json($cuentaBancaria, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar la cuenta bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'consultarCuentaBancaria',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarCuentaBancaria(Request $request)
    {
        try {
            // Validar que Detraccion sea único si es 1 y esté activo
            if ($request->Detraccion == 1) {
                $existeDetraccion = DB::table('cuentabancaria')
                    ->where('Detraccion', 1)
                    ->where('Vigente', 1) // Solo cuentas activas
                    ->exists();

                if ($existeDetraccion) {
                    Log::warning('Intento de registrar cuenta bancaria con Detracción ya existente', [
                        'Controlador' => 'EntidadBancariaController',
                        'Metodo' => 'registrarCuentaBancaria',
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['error' => 'Ya existe una cuenta activa con Detracción'], 400);
                }
            }

            // Crear la cuenta bancaria
            Log::info('Registrando nueva cuenta bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'registrarCuentaBancaria',
                'Datos' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            CuentaBancaria::create($request->all());

            return response()->json(['message' => 'Cuenta Bancaria registrada correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar la cuenta bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'registrarCuentaBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function actualizarCuentaBancaria(Request $request)
    {
        try {
            $cuentaBancaria = CuentaBancaria::find($request->Codigo);

            if (!$cuentaBancaria) {
                Log::warning('Cuenta Bancaria no encontrada para actualizar', [
                    'Controlador' => 'EntidadBancariaController',
                    'Metodo' => 'actualizarCuentaBancaria',
                    'codigo' => $request->Codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Cuenta Bancaria no encontrada'], 404);
            }

            // Si la cuenta bancaria que se está actualizando tiene Detraccion = 1
            if ($request->Detraccion == 1 && $request->Vigente == 1) {
                $existeOtraDetraccionActiva = DB::table('cuentabancaria')
                    ->where('Detraccion', 1)
                    ->where('Vigente', 1)
                    ->where('Codigo', '!=', $request->Codigo) // Excluir la cuenta que se está actualizando
                    ->exists();

                if ($existeOtraDetraccionActiva) {
                    Log::warning('Intento de actualizar cuenta bancaria con Detracción ya existente', [
                        'Controlador' => 'EntidadBancariaController',
                        'Metodo' => 'actualizarCuentaBancaria',
                        'codigo' => $request->Codigo,
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['error' => 'Ya existe otra cuenta activa con Detracción'], 400);
                }
            }
            Log::info('Actualizando cuenta bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'actualizarCuentaBancaria',
                'Datos' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            // Actualizar la cuenta bancaria
            $cuentaBancaria->update($request->all());

            return response()->json(['message' => 'Cuenta Bancaria actualizada correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar la cuenta bancaria', [
                'Controlador' => 'EntidadBancariaController',
                'Metodo' => 'actualizarCuentaBancaria',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
