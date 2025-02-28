<?php

namespace App\Http\Controllers\API\PagoComision;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\PagoComision;
use Illuminate\Support\Facades\Validator;
class PagoComisionController extends Controller
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

    public function registrarPagoComision(Request $request)
    {
        $egreso = $request->input('egreso');
        $pagoComision = $request->input('pagoComision');

        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();

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

            $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

            if($egreso['Monto'] > $total){
                return response()->json(['error' => 'No hay suficiente Efectivo en caja', 'Disponible' => $total ], 500);
            }

        }else if($egreso['CodigoSUNAT'] == '003'){
            $egreso['Lote'] = null;
            $egreso['Referencia'] = null;

        }else if($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006'){
            $egreso['CodigoCuentaBancaria'] = null;
            $egreso['CodigoBilleteraDigital'] = null;
        }

        DB::beginTransaction();
        try{

            $egreso = Egreso::create($egreso);
            $pagoComision['Codigo'] = $egreso->Codigo;
            $pagoComision['CodigoDocumentoVenta'] = $pagoComision['CodigoDocumentoVenta'] == 0 ? null : $pagoComision['CodigoDocumentoVenta'];
            $pagoComision['CodigoContrato'] = $pagoComision['CodigoContrato'] == 0 ? null : $pagoComision['CodigoContrato'];
    
            PagoComision::create($pagoComision);
            
            DB::commit();

            return response()->json([
                'message' => 'Pago de comisión registrado correctamente'
            ], 201);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar el pago de comisión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listarPagosComisiones(Request $request)
    {
        $data = $request->input('data');
        try{
            $resultados = DB::table('pagocomision as pc')
            ->select(
                'e.Codigo',
                DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Medico"),
                DB::raw('DATE(e.Fecha) as Fecha'),
                'e.Monto as Monto'
            )
            ->join('Egreso as e', 'e.Codigo', '=', 'pc.Codigo')
            ->join('Caja as c', 'c.Codigo', '=', 'e.CodigoCaja')
            ->join('Personas as p', 'p.Codigo', '=', 'pc.CodigoMedico')
            ->where('c.CodigoSede', $data['CodigoSede'])
            ->where('e.Vigente', 1)
            ->get();

            return response()->json($resultados, 200);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al listar los pagos de comisiones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarPagoComision($codigo)
    {
        try{
            $pagoComision = PagoComision::find($codigo);
            $egreso = Egreso::find($codigo);

            if ($pagoComision) {
                return response()->json([
                    'pagoComision' => $pagoComision,
                    'egreso' => $egreso
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Pago de comisión no encontrado'
                ], 404);
            }
            
        }catch(\Exception $e){
            return response()->json([
                'error' => 'Error al consultar el pago de comisión',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarPagoComision(Request $request, string $id)
    {
        
    }

    public function listarDocumentos(Request $request){

        $medico = $request->input('medico');
        $sede = $request->input('sede');
        $termino = $request->input('termino');
        $tipoComision = $request->input('tipoComision');

        try{

            if ($tipoComision == 'C') {
                $query = DB::table('ContratoProducto as cp')
                    ->join('personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                    ->leftJoin('pagoComision as pc', 'cp.Codigo', '=', 'pc.CodigoContrato')
                    ->select([
                        'cp.Codigo as Codigo',
                        DB::raw("LPAD(cp.NumContrato, 5, '0') AS Documento"),
                        DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Paciente"),
                        DB::raw("DATE(cp.Fecha) as Fecha")
                    ])
                    ->where('cp.CodigoMedico', $medico)
                    ->where('cp.CodigoSede', $sede)
                    ->where('cp.Vigente', 1)
                    ->whereNull('pc.Codigo')
                    ->where(function ($query) use ($termino) {
                        $query->where('p.Nombres', 'LIKE', "{$termino}%")
                              ->orWhere('p.Apellidos', 'LIKE', "{$termino}%");
                    });
                    
            }else{
                $query = DB::table('DocumentoVenta as dv')
                ->join('personas as p', 'p.Codigo', '=', 'dv.CodigoPaciente')
                ->leftJoin('pagoComision as pc', 'dv.Codigo', '=', 'pc.CodigoDocumentoVenta')
                ->select([
                    'dv.Codigo as Codigo',
                    DB::raw("CONCAT(dv.Serie,' - ',LPAD(dv.Numero, 5, '0')) as Documento"),
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Paciente"),
                    DB::raw("DATE(dv.Fecha) as Fecha")
                ])
                ->where('dv.CodigoMedico', $medico)
                ->where('dv.Vigente', 1)
                ->where('dv.CodigoSede', $sede)
                ->whereNull('dv.CodigoMotivoNotaCredito')
                ->whereNull('dv.CodigoContratoProducto')
                ->whereNull('pc.Codigo')
                ->where(function ($query) use ($termino) {
                    $query->where('p.Nombres', 'LIKE', "{$termino}%")
                          ->orWhere('p.Apellidos', 'LIKE', "{$termino}%");
                });
            }

            return response()->json($query->get(), 200);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al buscar los documentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
