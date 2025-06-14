<?php

namespace App\Http\Controllers\API\BilleteraDigital;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\BilleteraDigital;
use App\Models\Recaudacion\LocalBilleteraDigital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BilleteraDigitalController extends Controller
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

    public function listarEntidadBilleteraDigital()
    {
        try {
            $entidad = BilleteraDigital::all();

            // Log de éxito
            Log::info('Usuarios listados correctamente', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'listarEntidadBilleteraDigital',
                'cantidad' => count($entidad),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al listar entidad billetera', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'listarEntidadBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarEntidadBilleteraDigital(Request $request)
    {
        try {
            BilleteraDigital::create($request->all());
            // Log de éxito
            Log::info('Entidad Billetera Digital registrada correctamente', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'registrarEntidadBilleteraDigital',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Entidad Billetera Digital registrada correctamente'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al registrar entidad billetera digital.', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'registrarEntidadBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarEntidadBilleteraDigital($codigo)
    {
        try {
            $entidad = BilleteraDigital::find($codigo);
            if (!$entidad) {
                // Log del error específico
                Log::warning('Entidad Billetera Digital no encontrada', [
                    'Controlador' => 'BilleteraDigitalController',
                    'Metodo' => 'consultarEntidadBilleteraDigital',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['message' => 'Entidad Billetera Digital no encontrada'], 404);
            }
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al consultar entidad billetera digital.', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'consultarEntidadBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarEntidadBilleteraDigital(Request $request)
    {
        try {
            $entidad = BilleteraDigital::find($request->Codigo);
            $entidad->update($request->all());
            // Log de éxito
            Log::info('Entidad Billetera Digital actualizada correctamente', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'actualizarEntidadBilleteraDigital',
                'codigo' => $entidad->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Entidad Billetera Digital actualizada correctamente'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al actualizar entidad billetera digital.', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'actualizarEntidadBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    //Billetera Digital - Empresa

    public function listarBilleteraDigital($codigo)
    {
        try {
            $datos = DB::table('billeteradigital as bd')
                ->join('entidadbilleteradigital as ebd', 'ebd.Codigo', '=', 'bd.CodigoEntidadBilleteraDigital')
                ->join('empresas as e', 'e.Codigo', '=', 'bd.CodigoEmpresa')
                ->select(
                    'bd.Codigo',
                    'ebd.Nombre as Billetera',
                    'e.Nombre as Empresa',
                    'bd.Numero',
                    'bd.Vigente'
                )
                ->where('e.Codigo', $codigo)
                ->get();


            // Log de éxito
            Log::info('Billetera Digital listados correctamente', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'listarBilleteraDigital',
                'cantidad' => count($datos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($datos, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error inesperado al listar billetera digital', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'listarBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarBilleteraDigital(Request $request)
    {
        try {
            LocalBilleteraDigital::create($request->all());
            // Log de éxito
            Log::info('Billetera Digital registrada correctamente', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'registrarBilleteraDigital',
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Billetera Digital registrada correctamente'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al registrar billetera digital.', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'registrarBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarBilleteraDigital($codigo)
    {
        try {
            $entidad = LocalBilleteraDigital::find($codigo);
            if (!$entidad) {
                // Log del error específico
                Log::warning('Billetera Digital no encontrada', [
                    'Controlador' => 'BilleteraDigitalController',
                    'Metodo' => 'consultarBilleteraDigital',
                    'codigo' => $codigo,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['message' => 'Billetera Digital no encontrada'], 404);
            }
            return response()->json($entidad, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al consultar billetera digital.', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'consultarBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarBilleteraDigital(Request $request)
    {
        try {
            $entidad = LocalBilleteraDigital::find($request->Codigo);
            $entidad->update($request->all());
            // Log de éxito
            Log::info('Billetera Digital actualizada correctamente', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'actualizarBilleteraDigital',
                'codigo' => $entidad->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Billetera Digital actualizada correctamente'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al actualizar billetera digital.', [
                'Controlador' => 'BilleteraDigitalController',
                'Metodo' => 'actualizarBilleteraDigital',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'codigo' => $request->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
