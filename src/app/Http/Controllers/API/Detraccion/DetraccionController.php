<?php

namespace App\Http\Controllers\API\Detraccion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
class DetraccionController extends Controller
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

    public function listarDetraccionesPendientes($sede)
    {
        try{
            $ventas = DB::table('clinica_db.DocumentoVenta as dv')
                ->join('clinica_db.Detraccion as d', 'dv.Codigo', '=', 'd.CodigoDocumentoVenta')
                ->select(
                    'dv.Codigo as CodigoVenta',
                    'd.Codigo as CodDetraccion',
                    DB::raw('DATE(dv.Fecha) as Fecha'),
                    DB::raw("CONCAT(dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) as Documento"),
                    'd.Monto'
                )
                ->where('dv.CodigoSede', $sede) // Filtro por sede
                ->where('dv.Vigente', 1) // Solo documentos vigentes
                ->whereNull('d.CodigoPagoDetraccion') // Código de pago de detracción es NULL
                ->orderBy('dv.Fecha', 'desc')
                ->get();

            return response()->json($ventas);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        } 
    }
}
