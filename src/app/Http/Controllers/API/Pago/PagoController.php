<?php

namespace App\Http\Controllers\API\Pago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
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

    public function buscarPago(Request $request)
    {

        $fecha = $request->input('fecha');
        $codigoSede = $request->input('codigoSede');

        try {
            $pagos = DB::table('pago as p')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
                ->join('pagodocumentoventa as pdv', 'pdv.CodigoPago', '=', 'p.Codigo')
                ->join('documentoventa as d', 'd.Codigo', '=', 'pdv.CodigoDocumentoVenta')
                ->select(
                    'p.Codigo',
                    'p.NumeroOperacion',
                    'mp.Nombre',
                    DB::raw("DATE(p.Fecha) as Fecha"),
                    'p.Monto'
                )
                ->where('p.Vigente', 1)
                ->where('pdv.Vigente', 0)
                ->where(DB::raw("DATE(p.Fecha)"), $fecha)  // Reemplaza esto con el valor de $fecha
                ->where('d.CodigoSede', $codigoSede)                       // Reemplaza esto con el valor de $codigoSede
                ->get();

            return response()->json($pagos, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
