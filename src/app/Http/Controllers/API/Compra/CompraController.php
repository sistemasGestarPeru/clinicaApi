<?php

namespace App\Http\Controllers\API\Compra;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Compra;
use App\Models\Recaudacion\Cuota;
use App\Models\Recaudacion\DetalleCompra;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagoProveedor;
use App\Models\Recaudacion\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
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


    public function listarProveedor(Request $request)
    {

        $nombre = $request->input('nombre');
        $ruc = $request->input('ruc');

        try {
            $proveedores = DB::table('clinica_db.proveedor')
                ->where('Vigente', 1)
                // ->where('RazonSocial', 'like', '%'.$nombre.'%')
                // ->where('RUC', 'like', '%'.$ruc.'%')
                ->select('Codigo', 'RazonSocial', 'RUC')
                ->get();

            return response()->json($proveedores, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los proveedores'], 500);
        }
    }

    public function listarProducto(Request $request)
    {

        try {
            $productos = DB::table('producto')
                ->select('Codigo', 'Nombre', 'TipoGravado')
                ->where('Tipo', 'B')
                ->where('Vigente', 1)
                ->get();
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los productos'], 500);
        }
    }

    public function listarCompras(Request $request)
    {

        try {

            $compra = DB::table('compra as c')
                ->join('proveedor as p', 'p.Codigo', '=', 'c.CodigoProveedor')
                ->select('c.Codigo', 'c.Serie', 'c.Numero', 'c.Fecha', 'p.RazonSocial')
                ->where('c.Vigente', '=', 1)
                ->get();

            return response()->json($compra, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar las compras'], 500);
        }
    }

    public function listarPagosAdelantados(Request $request)
    {
        $Proveedor = $request->input('proveedor');

        try {
            $result = DB::table('PagoProveedor as pp')
                ->join('Egreso as e', 'e.Codigo', '=', 'pp.Codigo')
                ->select(
                    'e.Codigo as CodigoE',
                    'pp.TipoMoneda',
                    'e.Fecha',
                    DB::raw("
                        CASE 
                            WHEN pp.TipoMoneda = 'S' THEN e.Monto
                            ELSE pp.MontoMonedaExtranjera
                        END AS Monto
                        ")
                )
                ->where('pp.CodigoProveedor', $Proveedor)
                ->whereNull('pp.CodigoCuota')
                ->get();
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los pagos adelantados'], 500);
        }
    }


    public function registrarCompra(Request $request)
    {

        $compra = $request->input('compra');
        $detalleCompra = $request->input('detalleCompra');
        $cuotas = $request->input('cuota');
        $egreso = $request->input('egreso');
        $proveedor = $request->input('proveedor');

        $egreso['CodigoCaja'] = 65;
        $proveedor['CodigoProveedor'] = $compra['CodigoProveedor'];

        if ($egreso['CodigoCuentaOrigen'] == 0) {
            $egreso['CodigoCuentaOrigen'] = null;
        }

        $MontoTotal = 0;

        try {
            DB::beginTransaction();

            $compraData = Compra::create($compra);
            $idCompra = $compraData->Codigo;

            foreach ($detalleCompra as $detalle) {
                $detalle['CodigoCompra'] = $idCompra;
                $MontoTotal += $detalle['MontoTotal'];
                DetalleCompra::create($detalle);
            }

            $nuevoEgreso = Egreso::create($egreso);
            $idEgreso = $nuevoEgreso->Codigo;

            foreach ($cuotas as $cuota) {

                $cuota['CodigoCompra'] = $idCompra;
                $cutaData = Cuota::create($cuota);
                $idCuota = $cutaData->Codigo;

                if (!empty($cuota['CodigoE'])) {
                    DB::table('pagoproveedor')
                        ->where('Codigo', $cuota['CodigoE'])
                        ->update(['CodigoCuota' => $idCuota]);
                } else {
                    $proveedor['Codigo'] = $idEgreso;
                    $proveedor['CodigoCuota'] = $idCuota;
                    PagoProveedor::create($proveedor);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Compra registrada correctamente', 'cuotas' => $cuotas], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la compra', 'error' => $e, 'cuotas' => $cuotas], 500);
        }
    }
}
