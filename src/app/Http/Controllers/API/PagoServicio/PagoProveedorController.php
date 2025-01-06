<?php

namespace App\Http\Controllers\API\PagoServicio;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Http\Requests\Recaudacion\PagoProveedor\PagoProveedorRequest as GuardarPagoProveedorRequest;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagoProveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagoProveedorController extends Controller
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


    public function registrarPago(Request $request)
    {
        $pagoProveedor = $request->input('pagoProveedor');
        $egreso = $request->input('egreso');

        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();

        //Validar Pago Proveedor
        $pagoProveedorValidator = Validator::make($pagoProveedor, (new GuardarPagoProveedorRequest())->rules());
        $pagoProveedorValidator->validate();

        if ($egreso['CodigoCuentaOrigen'] == 0) {
            $egreso['CodigoCuentaOrigen'] = null;
        }

        DB::beginTransaction();
        try {
            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoProveedor['Codigo'] = $idEgreso;
            PagoProveedor::create($pagoProveedor);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e], 500);
        }
    }


    public function listarComprasProveedores(Request $request)
    {
        try {

            $resultado = DB::table('Compra as C')
                ->join('Proveedor as P', 'P.Codigo', '=', 'C.CodigoProveedor')
                ->select(
                    'C.Codigo as Compra',
                    'P.Codigo as Proveedor',
                    'P.RazonSocial',
                    'C.Fecha',
                    'C.Serie',
                    'C.Numero'
                )
                ->orderBy('C.Codigo')
                ->get();

            return response()->json($resultado, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarCuotasProveedor(Request $request)
    {
        $codigoCompra = $request->input('codigoCompra');

        try {
            $resultado = DB::table('Cuota as C')
                ->select(
                    'C.Codigo',
                    'C.Fecha',
                    'C.Monto',
                    DB::raw('CASE WHEN PP.Codigo IS NULL THEN 1 ELSE 0 END AS Condicion'),
                    'CO.FormaPago'
                )
                ->leftJoin('PagoProveedor as PP', 'C.Codigo', '=', 'PP.CodigoCuota')
                ->join('Compra as CO', 'C.CodigoCompra', '=', 'CO.Codigo')
                ->where('C.CodigoCompra', '=', $codigoCompra)
                ->where('C.Vigente', '=', 1)
                ->get();

            return response()->json($resultado, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function pagarCuota(Request $request)
    {

        $pagoProveedor = $request->input('pagoProveedor');
        $egreso = $request->input('egreso');

        try {

            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoProveedor['Codigo'] = $idEgreso;

            //agregar pago proveedor    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
