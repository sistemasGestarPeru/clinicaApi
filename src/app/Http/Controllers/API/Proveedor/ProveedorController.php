<?php

namespace App\Http\Controllers\API\Proveedor;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Proveedor;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
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

    public function listarProveedor()
    {
        try {
            $entidad = Proveedor::all();
            //log info
            Log::info('Listar Proveedores', [
                'Controlador' => 'proveedor',
                'Metodo' => 'listarProveedor',
                'Cantidad' => $entidad->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar Proveedores', [
                'Controlador' => 'proveedor',
                'Metodo' => 'listarProveedor',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar Proveedores', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarProveedor($codigo)
    {
        try {
            $entidad = Proveedor::find($codigo);
            //log info
            Log::info('Consultar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'consultarProveedor',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($entidad, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'consultarProveedor',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => 'Ocurrió un error al consultar Proveedores', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarProveedor(Request $request)
    {
        try {
            Proveedor::create($request->all());

            //log info
            Log::info('Registrar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'registrarProveedor',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Proveedor registrado correctamente'], 200);
        } catch (QueryException $e) {
            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {

                //log warning
                Log::warning('Error al registrar Proveedor', [
                    'Controlador' => 'proveedor',
                    'Metodo' => 'registrarProveedor',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'Comando' => $request->all()
                ]);

                return response()->json([
                    'error' => 'El RUC del Proveedor ingresado ya existe. Intente con otro RUC.'
                ], 500);
            }


            //log error
            Log::error('Error al registrar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'registrarProveedor',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'registrarProveedor',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }

    public function actualizarProveedor(Request $request)
    {
        try {
            $entidad = Proveedor::find($request->Codigo);
            $entidad->update($request->all());

            //log info
            Log::info('Actualizar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'actualizarProveedor',
                'Codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Proveedor actualizado correctamente'], 200);
        } catch (QueryException $e) {

            // Verificar si el error es por clave duplicada (código SQL 1062)
            if ($e->errorInfo[1] == 1062) {

                //log warning
                Log::warning('Error al actualizar Proveedor', [
                    'Controlador' => 'proveedor',
                    'Metodo' => 'actualizarProveedor',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'Comando' => $request->all()
                ]);

                return response()->json([
                    'error' => 'El RUC del Proveedor ingresado ya existe. Intente con otro RUC.'
                ], 500);
            }

            //log error
            Log::error('Error al actualizar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'actualizarProveedor',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores de SQL
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar Proveedor', [
                'Controlador' => 'proveedor',
                'Metodo' => 'actualizarProveedor',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            // Capturar otros errores inesperados
            return response()->json([
                'error' => 'Ocurrió un error inesperado. Inténtelo nuevamente.'
            ], 500);
        }
    }
}
