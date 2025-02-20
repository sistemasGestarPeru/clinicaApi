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

        if(isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0){
            $egreso['CodigoCuentaOrigen'] = null;
        }

        if(isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0){
            $egreso['CodigoBilleteraDigital'] = null;
        }

        if ($egreso['CodigoSUNAT'] == '008') {
            $egreso['CodigoCuentaOrigen'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;
            $egreso['NumeroOperacion'] = null;

        }else if($egreso['CodigoSUNAT'] == '003'){
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;

        }else if($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006'){
            $egreso['CodigoCuentaBancaria'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
        }

        DB::beginTransaction();
        try {
            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;

            $pagoProveedor['Codigo'] = $idEgreso;
            PagoProveedor::create($pagoProveedor);

            DB::commit();

            return response()->json(['message' => 'Pago registrado correctamente'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e], 500);
        }
    }


    public function listarComprasProveedores(Request $request)
    {
        try {

        $resultado = DB::table('compra as C')
            ->join('Cuota as CU', 'CU.CodigoCompra', '=', 'C.Codigo')
            ->leftJoin('PagoProveedor as PP', 'PP.CodigoCuota', '=', 'CU.Codigo')
            ->join('Proveedor as P', 'P.Codigo', '=', 'C.CodigoProveedor')
            ->select(
                'C.Codigo as Compra',
                'C.Fecha',
                'C.Serie',
                'C.Numero',
                'P.Codigo as Proveedor',
                'P.RazonSocial'
            )
            ->where('CU.Vigente', 1)
            ->where('C.Vigente', 1)
            
            ->groupBy(
                'C.Codigo',
                'C.Fecha',
                'C.Serie',
                'C.Numero',
                'P.Codigo',
                'P.RazonSocial'
            )
            
            ->havingRaw('SUM(CASE WHEN PP.Codigo IS NULL THEN 1 ELSE 0 END) > 0')
            ->orderBy('C.Fecha', 'DESC')
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
            $resultado = DB::table('cuota AS C')
            ->select(
                'C.Codigo',
                'C.Fecha',
                'C.Monto',
                DB::raw("
                    CASE
                        WHEN tm.Siglas = 'PEN' THEN COALESCE(SUM(e.Monto), 0)
                        ELSE COALESCE(SUM(PP.MontoMonedaExtranjera), 0)
                    END AS MontoTotalPagado
                "),
                DB::raw("
                    CASE
                        WHEN tm.Siglas = 'PEN' THEN (C.Monto - COALESCE(SUM(e.Monto), 0))
                        ELSE (C.Monto - COALESCE(SUM(PP.MontoMonedaExtranjera), 0))
                    END AS MontoRestante
                "),
                'C.TipoMoneda AS CodMoneda',
                'tm.Siglas AS TipoMoneda',
                
            )
            ->leftJoin('PagoProveedor AS PP', 'C.Codigo', '=', 'PP.CodigoCuota')
            ->leftJoin('egreso AS e', 'e.Codigo', '=', 'PP.Codigo')
            ->leftJoin('tipomoneda AS tm', 'C.TipoMoneda', '=', 'tm.Codigo')
            ->where('C.CodigoCompra', '=', $codigoCompra)
            ->groupBy('C.Codigo', 'C.Monto', 'C.Fecha', 'C.TipoMoneda', 'tm.Siglas')
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
