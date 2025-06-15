<?php

namespace App\Http\Controllers\API\LocalDocumentoVenta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\LocalDocumentoVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalDocumentoVentaController extends Controller
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

    public function consultarSedeDocumentoVenta($codigo)
    {
        try {
            $sedeDocVenta = LocalDocumentoVenta::find($codigo);
            Log::info('Consultar Documento Venta Sede', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'consultarSedeDocumentoVenta',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($sedeDocVenta, 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Documento Venta Sede', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'consultarSedeDocumentoVenta',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json($e, 500);
        }
    }

    public function registrarSedeDocVenta(Request $request)
    {
        $sedeDocVenta = $request->input('documento');

        if ($sedeDocVenta['CodigoSerieDocumentoVenta'] == 0) {
            $sedeDocVenta['CodigoSerieDocumentoVenta'] = null;
        }

        try {
            LocalDocumentoVenta::create($sedeDocVenta);
            Log::info('Registrar Sede Documento Venta', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'registrarSedeDocVenta',
                'CodigoSede' => $sedeDocVenta['CodigoSede'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Sede Documento Venta registrado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al registrar Sede Documento Venta', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'registrarSedeDocVenta',
                'CodigoSede' => $sedeDocVenta['CodigoSede'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json($e, 500);
        }
    }

    public function listarSedeDocumentoVenta($sede)
    {
        try {
            $resultados = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->join('sedesrec as s', 's.Codigo', '=', 'ldv.CodigoSede')
                ->select(
                    'ldv.Codigo',
                    'tdv.Nombre',
                    DB::raw("CASE WHEN ldv.TipoProducto = 'B' THEN 'BIEN' ELSE 'SERVICIO' END AS TipoProducto"),
                    'ldv.Serie',
                    's.Nombre as NombreSede',
                    'ldv.Vigente'
                )
                ->where('ldv.CodigoSede', $sede)
                ->where('tdv.Vigente', 1)
                ->get();

            Log::info('Listar Sede Documento Venta', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'listarSedeDocumentoVenta',
                'CodigoSede' => $sede,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Sede Documento Venta', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'listarSedeDocumentoVenta',
                'CodigoSede' => $sede,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json($e, 500);
        }
    }

    public function listarDocumentosReferencia($sede, $consultaDoc)
    {
        try {

            $documentos = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tpv', 'ldv.CodigoTipoDocumentoVenta', '=', 'tpv.Codigo')
                ->leftJoin('localdocumentoventa as subquery', function ($join) use ($sede) {
                    $join->on('ldv.Codigo', '=', 'subquery.CodigoSerieDocumentoVenta')
                        ->where('subquery.CodigoSede', '=', $sede)
                        ->whereNotNull('subquery.CodigoSerieDocumentoVenta');
                })
                ->where(function ($query) use ($sede, $consultaDoc) {
                    $query->where(function ($q) use ($sede) {
                        $q->where('ldv.CodigoSede', '=', $sede)
                            ->where('tpv.Tipo', '=', 'V')
                            // ->whereNotNull('tpv.CodigoSUNAT')
                            ->whereNull('subquery.CodigoSerieDocumentoVenta');
                    })
                        ->orWhere('subquery.CodigoSerieDocumentoVenta', '=', $consultaDoc);
                })
                ->select('ldv.Codigo', 'ldv.Serie', 'tpv.Nombre', 'ldv.TipoProducto')
                ->get();

            // $documentos = DB::table('localdocumentoventa as ldv')
            //     ->join('tipodocumentoventa as tpv', 'ldv.CodigoTipoDocumentoVenta', '=', 'tpv.Codigo')
            //     ->leftJoin('localdocumentoventa as subquery', function ($join) use ($sede) { 
            //         $join->on('ldv.Codigo', '=', 'subquery.CodigoSerieDocumentoVenta')
            //             ->where('subquery.CodigoSede', $sede)
            //             ->whereNotNull('subquery.CodigoSerieDocumentoVenta');
            //     })
            //     ->where('ldv.CodigoSede', $sede)
            //     ->where('tpv.Tipo', 'V')
            //     ->whereNotNull('tpv.CodigoSUNAT')
            //     ->whereNull('subquery.CodigoSerieDocumentoVenta') 
            //     ->select('ldv.Codigo', 'ldv.Serie', 'tpv.Nombre', 'ldv.TipoProducto')
            //     ->get();

            Log::info('Listar Documentos Referencia', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'listarDocumentosReferencia',
                'CodigoSede' => $sede,
                'ConsultaDoc' => $consultaDoc,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($documentos, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar Documentos Referencia', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'listarDocumentosReferencia',
                'CodigoSede' => $sede,
                'ConsultaDoc' => $consultaDoc,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json($e, 500);
        }
    }


    public function actualizarSedeDocVenta(Request $request)
    {
        $sedeDocVenta = $request->input('documento');
        try {
            //Actualizar todos los campos
            LocalDocumentoVenta::where('Codigo', $sedeDocVenta['Codigo'])
                ->update([
                    'CodigoTipoDocumentoVenta' => $sedeDocVenta['CodigoTipoDocumentoVenta'],
                    'Serie' => $sedeDocVenta['Serie'],
                    'TipoProducto' => $sedeDocVenta['TipoProducto'],
                    'Vigente' => $sedeDocVenta['Vigente'],
                    'CodigoSede' => $sedeDocVenta['CodigoSede'],
                    'CodigoSerieDocumentoVenta' => $sedeDocVenta['CodigoSerieDocumentoVenta']
                ]);

            Log::info('Actualizar Sede Documento Venta', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'actualizarSedeDocVenta',
                'CodigoSede' => $sedeDocVenta['CodigoSede'],
                'CodigoDocumento' => $sedeDocVenta['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
        } catch (\Exception $e) {

            Log::error('Error al actualizar Sede Documento Venta', [
                'Controlador' => 'LocalDocumentoVentaController',
                'Metodo' => 'actualizarSedeDocVenta',
                'CodigoSede' => $sedeDocVenta['CodigoSede'],
                'CodigoDocumento' => $sedeDocVenta['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['error' => 'Ocurrió un error al actualizar Sede Documento Venta.', 'bd' => $e->getMessage()], 500);
        }
    }
}
