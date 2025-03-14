<?php

namespace App\Http\Controllers\API\LocalDocumentoVenta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\LocalDocumentoVenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function consultarSedeDocumentoVenta($codigo){
        try{
            $sedeDocVenta = LocalDocumentoVenta::find($codigo);
            return response()->json($sedeDocVenta, 200);
        }catch(\Exception $e){
            return response()->json($e, 500);
        }
    }

    public function registrarSedeDocVenta(Request $request){
        $sedeDocVenta = $request->input('documento');

        if($sedeDocVenta['CodigoSerieDocumentoVenta'] == 0){
            $sedeDocVenta['CodigoSerieDocumentoVenta'] = null;
        }

        try{
            LocalDocumentoVenta::create($sedeDocVenta);
            return response()->json(['message' => 'Sede Documento Venta registrado correctamente'], 200);
        }catch(\Exception $e){
            return response()->json($e, 500);
        }
    }

    public function listarSedeDocumentoVenta($sede){
        try{
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
            return response()->json($resultados, 200);
        }catch(\Exception $e){
            return response()->json($e, 500);
        }
    }

    public function listarDocumentosReferencia($sede){
        try{
            $documentos = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tpv', 'ldv.CodigoTipoDocumentoVenta', '=', 'tpv.Codigo')
                ->leftJoin('localdocumentoventa as subquery', function ($join) use ($sede) { 
                    $join->on('ldv.Codigo', '=', 'subquery.CodigoSerieDocumentoVenta')
                        ->where('subquery.CodigoSede', $sede)
                        ->whereNotNull('subquery.CodigoSerieDocumentoVenta');
                })
                ->where('ldv.CodigoSede', $sede)
                ->where('tpv.Tipo', 'V')
                ->whereNotNull('tpv.CodigoSUNAT')
                ->whereNull('subquery.CodigoSerieDocumentoVenta') 
                ->select('ldv.Codigo', 'ldv.Serie', 'tpv.Nombre', 'ldv.TipoProducto')
                ->get();
            return response()->json($documentos, 200);
        }catch(\Exception $e){
            return response()->json($e, 500);
        }
    }
}
