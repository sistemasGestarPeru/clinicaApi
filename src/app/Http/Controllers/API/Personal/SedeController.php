<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SedeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Sede::all();
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

    public function listarEmpresas()
    {
        try {

            $empresas = DB::table('empresas')
                ->select('Codigo', 'RazonSocial')
                ->where('Vigente', 1)
                ->get();

            //log info
            Log::info('Listar Empresas', [
                'Controlador' => 'SedeController',
                'Metodo' => 'listarEmpresas',
                'Cantidad' => $empresas->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($empresas, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar empresas', [
                'Controlador' => 'SedeController',
                'Metodo' => 'listarEmpresas',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function listarSedes()
    {
        try {
            $sede = DB::table('sedesrec as s')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                // ->join('departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
                ->select(
                    's.Codigo',
                    // 'd.Nombre as Departamento',
                    'e.Nombre as Empresa',
                    's.Nombre as Sede',
                    's.Direccion',
                    's.Telefono1 as Telefono',
                    's.Telefono2',
                    's.Telefono3',
                    's.Vigente'
                )
                ->get();

            //log info
            Log::info('Listar Sedes', [
                'Controlador' => 'SedeController',
                'Metodo' => 'listarSedes',
                'Cantidad' => $sede->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($sede, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar sedes', [
                'Controlador' => 'SedeController',
                'Metodo' => 'listarSedes',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarSede(Request $request)
    {
        try {
            Sede::create($request->all());
            //log info
            Log::info('Registrar Sede', [
                'Controlador' => 'SedeController',
                'Metodo' => 'registrarSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Sede registrada correctamente'], 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al registrar sede', [
                'Controlador' => 'SedeController',
                'Metodo' => 'registrarSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarSede($codigo)
    {
        try {
            $sede = Sede::find($codigo);
            //log info
            Log::info('Consultar Sede', [
                'Controlador' => 'SedeController',
                'Metodo' => 'consultarSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_sede' => $codigo
            ]);
            return response()->json($sede, 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al consultar sede', [
                'Controlador' => 'SedeController',
                'Metodo' => 'consultarSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_sede' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarSede(Request $request)
    {
        try {
            $sede = Sede::find($request->Codigo);
            $sede->update($request->all());

            //log info
            Log::info('Actualizar Sede', [
                'Controlador' => 'SedeController',
                'Metodo' => 'actualizarSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_sede' => $request->Codigo
            ]);

            return response()->json(['message' => 'Sede actualizada correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar sede', [
                'Controlador' => 'SedeController',
                'Metodo' => 'actualizarSede',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_sede' => $request->Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
