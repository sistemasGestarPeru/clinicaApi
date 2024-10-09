<?php

namespace App\Http\Controllers\API\Pago;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Pago;
use App\Models\Recaudacion\PagoDocumentoVenta;
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

    public function registrarPagoDocumentoVenta(Request $request)
    {

        $pagoDocData = $request->input('pagoDocVenta');
        try {
            DB::table('pagodocumentoventa')->insert([
                'CodigoPago' => $pagoDocData['CodigoPago'],
                'CodigoDocumentoVenta' =>  $pagoDocData['CodigoDocumentoVenta'],
                'Monto' => $pagoDocData['Monto']
            ]);
            return response()->json([
                'message' => 'Pago Asociado correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
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
                    DB::raw('DATE(p.Fecha) as Fecha'),
                    'p.Monto'
                )
                ->where('p.Vigente', 1)
                ->where('pdv.Vigente', 0)
                ->where(DB::raw('DATE(p.Fecha)'), $fecha)
                ->where('d.CodigoSede', $codigoSede)
                ->where(function ($query) {
                    $query->whereRaw('pdv.Codigo = (SELECT pdv_sub.Codigo FROM pagodocumentoventa pdv_sub WHERE pdv_sub.CodigoPago = p.Codigo ORDER BY pdv_sub.Codigo DESC LIMIT 1)');
                })
                ->get();

            return response()->json($pagos, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function buscarVentas(Request $request)
    {
        $fecha = $request->input('fecha');
        $codigoSede = $request->input('codigoSede');

        try {

            $ventas = DB::table('documentoventa as dv')
                ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->whereNull('pdv.Codigo')
                ->where('dv.Vigente', 1)
                ->select('dv.Codigo', 'dv.CodigoTipoDocumentoVenta', 'dv.Serie', 'dv.Numero', DB::raw("DATE(dv.Fecha) as Fecha"), 'dv.MontoTotal', 'dv.MontoPagado')
                ->where(DB::raw("DATE(dv.Fecha)"), $fecha)  // Reemplaza esto con el valor de $fecha
                ->where('dv.CodigoSede', $codigoSede)
                ->get();

            return response()->json($ventas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function anularPago(Request $request)
    {
        $codigoPago = $request->input('codigoPago');
        $codigoTrabajador = $request->input('codigoTrabajador');
        try {

            DB::table('pago')
                ->where('Codigo', $codigoPago)
                ->update([
                    'CodigoTrabajador' => $codigoTrabajador,
                    'Vigente' => 0
                ]);

            return response()->json([
                'message' => 'Pago anulado correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function consultarPago(Request $request)
    {
        $codigoPago = $request->input('codigoPago');

        try {
            $pago = DB::table('Pago')
                ->select('Codigo', 'CodigoMedioPago', 'CodigoCuentaBancaria', 'NumeroOperacion', DB::raw("DATE(Fecha) as Fecha"), 'Monto', 'CodigoBilleteraDigital')
                ->where('Vigente', 1)
                ->where('Codigo', $codigoPago)
                ->first(); // Obtiene el primer registro que cumple con la condición
            return response()->json($pago, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function editarPago(Request $request)
    {
        $pagoData = $request->input('pago');

        try {
            DB::table('pago')
                ->where('Codigo', $pagoData['Codigo'])
                ->update([
                    'CodigoMedioPago' => $pagoData['CodigoMedioPago'],
                    'CodigoCuentaBancaria' => $pagoData['CodigoCuentaBancaria'],
                    'NumeroOperacion' => $pagoData['NumeroOperacion'],
                    'Fecha' => $pagoData['Fecha'],
                    'Monto' => $pagoData['Monto'],
                    'CodigoBilleteraDigital' => $pagoData['CodigoBilleteraDigital'],
                    'CodigoTrabajador' => $pagoData['CodigoTrabajador']
                ]);

            return response()->json([
                'message' => 'Pago actualizado correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
