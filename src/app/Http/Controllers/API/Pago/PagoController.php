<?php

namespace App\Http\Controllers\API\Pago;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Pago\RegistrarPagoRequest;
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
    public function registrarPago(RegistrarPagoRequest $request)
    {
        // Verificación de los datos
        if ($request['CodigoMedioPago'] == 1) {
            $request['CodigoCuentaBancaria'] = null;
            $request['NumeroOperacion'] = null;
        }
    
        try {
            // Intentar crear el pago
            Pago::create($request->all());
            return response()->json(['message' => 'Pago registrado correctamente'], 200);
            
        } catch (\Exception $e) {
            // En caso de error, devuelve el mensaje de error y un código de estado 500 (error interno del servidor)
            return response()->json(['message' => 'Error al registrar el Pago', 'error' => $e->getMessage()], 500);
        }
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
            ->select(
                'p.Codigo',
                DB::raw("CASE WHEN p.CodigoMedioPago = 2 THEN p.NumeroOperacion ELSE '-' END AS NumeroOperacion"),
                'mp.Nombre',
                DB::raw('DATE(p.Fecha) as Fecha'),
                'p.Monto'
            )
            ->join('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
            ->leftJoin('pagodocumentoventa as pdv', function ($join) {
                $join->on('pdv.CodigoPago', '=', 'p.Codigo')
                     ->where('pdv.Vigente', '=', 1);
            })
            ->leftJoin('documentoventa as d', function ($join) use ($codigoSede) {
                $join->on('d.Codigo', '=', 'pdv.CodigoDocumentoVenta')
                     ->where('d.CodigoSede', '=', $codigoSede);
            })
            ->where('p.Vigente', 1)
            ->where(function ($query) {
                $query->where('d.Vigente', 0)
                      ->orWhereNull('pdv.Codigo');
            })
            // ->whereDate('p.Fecha', $fecha)
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
        $codigoPago = $request->input('codigoPago');

        try {

            $ventas = DB::table('documentoventa as dv')
            ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
            ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
            ->whereNull('pdv.Codigo')
            ->where('dv.Vigente', 1)
            ->where('dv.CodigoSede', $codigoSede)
            ->where('dv.MontoTotal', '>=', function ($query) use ($codigoPago) {
                $query->select('p.Monto')
                    ->from('pago as p')
                    ->where('p.Codigo', $codigoPago)
                    ->where('p.Vigente', 1)
                    ->limit(1);
            })
            ->select('dv.Codigo', 'tdv.Nombre', 'dv.Serie', 'dv.Numero', DB::raw('DATE(dv.Fecha) as Fecha'), 'dv.MontoTotal', 'dv.MontoPagado')
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
        
        DB::beginTransaction();
        try {
            // Verificar si existe el registro en 'pagodocumentoventa'
            $registroExiste = DB::table('pagodocumentoventa')
                ->where('CodigoPago', $codigoPago)
                ->exists();
    
            if ($registroExiste) {
                // Actualizar 'pagodocumentoventa' si existe
                DB::table('pagodocumentoventa')
                    ->where('CodigoPago', $codigoPago)
                    ->update([
                        'Vigente' => 0
                    ]);
            }
    
            // Verificar si existe el registro en 'pago'
            $registroPagoExiste = DB::table('pago')
                ->where('Codigo', $codigoPago)
                ->exists();
    
            if ($registroPagoExiste) {
                // Actualizar 'pago' si existe
                DB::table('pago')
                    ->where('Codigo', $codigoPago)
                    ->update([
                        'CodigoTrabajador' => $codigoTrabajador,
                        'Vigente' => 0
                    ]);
            }else{
                DB::rollBack();
                return response()->json([
                    'message' => 'El Pago no existe.',
                ], 404);
            }

            DB::commit();
            return response()->json([
                'message' => 'Pago anulado correctamente.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular el Pago.',
                'error' => $e->getMessage()
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
