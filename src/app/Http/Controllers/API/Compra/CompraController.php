<?php

namespace App\Http\Controllers\API\Compra;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Compra;
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
                ->select('Codigo', 'Nombre', 'Monto', 'TipoGravado')
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


    public function registrarCompra(Request $request)
    {

        $compra = $request->input('compra');
        $detalleCompra = $request->input('detalleCompra');
        $cuotas = $request->input('cuota');

        $MontoTotal = 0;

        try {
            DB::beginTransaction();

            $compraData = Compra::create($compra);
            $idCompra = $compraData->Codigo;

            foreach ($detalleCompra as &$detalle) {
                $detalle['CodigoCompra'] = $idCompra;
                $MontoTotal += $detalle['MontoTotal'];
            }

            DB::table('detalleCompra')->insert($detalleCompra);

            if ($cuotas != null && count($cuotas) > 0) {

                if ($compraData['FormaPago'] == 'C') { // Generar la diferencia del adelanto y crear cuota
                    $diferencia = $MontoTotal - $cuotas[0]['Monto'];

                    if ($diferencia > 0) {
                        printf("Diferencia: %d\n", $diferencia);
                        $nuevaCuota = array_merge([], $cuotas[0]);  // Crear una copia de la primera cuota
                        $nuevaCuota['Monto'] = $diferencia;  // Ajustar el monto en la nueva cuota

                        array_push($cuotas, $nuevaCuota);  // Agregar la nueva cuota al final del array
                    } else {
                        printf("Diferencia: %d\n", $diferencia);
                    }
                }

                foreach ($cuotas as &$cuota) { //Cuando es al Contado este tiene 1 cuota que refleja lo adelantado
                    $cuota['CodigoCompra'] = $idCompra;
                }

                DB::table('cuota')->insert($cuotas);
            }


            DB::commit();
            return response()->json(['message' => 'Compra registrada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la compra', 'error' => $e], 500);
        }
    }
}
