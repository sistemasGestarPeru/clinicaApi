<?php

namespace App\Http\Controllers\API\Personal;

use App\Http\Controllers\Controller;
use App\Models\Personal\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Empresa::all();
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
            $empresa = Empresa::all();
            //log info
            Log::info('Listar Empresas', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'listarEmpresas',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Cantidad' => $empresa->count()
            ]);
            return response()->json($empresa, 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al listar empresas', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'listarEmpresas',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarEmpresa(Request $request)
    {
        try {
            Empresa::create($request->all());
            //log info
            Log::info('Registrar Empresa', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'registrarEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Empresa registrada correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar empresa', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'registrarEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Comando' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarEmpresa($codigo)
    {
        try {
            $empresa = Empresa::find($codigo);
            //log info
            Log::info('Consultar Empresa', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'consultarEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $codigo
            ]);
            return response()->json($empresa, 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al consultar empresa', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'consultarEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEmpresa(Request $request)
    {
        try {
            $empresa = Empresa::find($request->Codigo);
            $empresa->update($request->all());
            //log info
            Log::info('Actualizar Empresa', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'actualizarEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $request->Codigo
            ]);
            return response()->json(['message' => 'Empresa actualizada correctamente'], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar empresa', [
                'Controlador' => 'EmpresaController',
                'Metodo' => 'actualizarEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Codigo' => $request->Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
