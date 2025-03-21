<?php

namespace App\Http\Controllers\API\PagoComision;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Models\Recaudacion\Comision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\PagoComision;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
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
        $comision = $request->input('comision');

        if($egreso){ 
            //Validar Egreso
            $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();

            if(isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0){
                $egreso['CodigoCuentaOrigen'] = null;
            }

            if(isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0){
                $egreso['CodigoBilleteraDigital'] = null;
            }

            $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
            $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
            $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

            if ($fechaCajaVal < $fechaVentaVal) {
                return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura de caja.'], 400);
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
        }

        
        DB::beginTransaction();
        try{

            $comision['CodigoDocumentoVenta'] = $comision['CodigoDocumentoVenta'] == 0 ? null : $comision['CodigoDocumentoVenta'];
            $comision['CodigoContrato'] = $comision['CodigoContrato'] == 0 ? null : $comision['CodigoContrato'];

            if($egreso && $pagoComision){
                $egreso = Egreso::create($egreso);
                $pagoComision['Codigo'] = $egreso->Codigo;        
                $codigoPagoComision = PagoComision::create($pagoComision)->Codigo;
                $comision['CodigoPagoComision'] = $codigoPagoComision;
            }

            Comision::create($comision);
            
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
        $sede = $data['CodigoSede'];
        try{

            $resultados = DB::table('comision as c')
            ->leftJoin('pagocomision as pc', 'c.CodigoPagoComision', '=', 'pc.Codigo')
            ->leftJoin('egreso as e', 'e.Codigo', '=', 'pc.Codigo')
            ->leftJoin('documentoventa as dv', 'c.CodigoDocumentoVenta', '=', 'dv.Codigo')
            ->leftJoin('contratoproducto as cp', 'c.CodigoContrato', '=', 'cp.Codigo')
            ->leftJoin('personas as p', 'c.CodigoMedico', '=', 'p.Codigo')
            ->select(
                'c.Codigo',
                'e.Codigo as Egreso',
                DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Medico"),
                DB::raw("CASE 
                            WHEN c.TipoDocumento = 'R' THEN 'Recibo por Honorario' 
                            ELSE 'Nota de Pago' 
                        END AS TipoDocumento"),
                'c.Monto',
                DB::raw("CONCAT(c.Serie, ' - ', c.Numero) as Documento"),
                DB::raw("DATE(e.Fecha) as FechaPago"),
                'c.Vigente'
            )
            ->where(function ($query) use ($sede) {
                $query->where('cp.CodigoSede', $sede)
                    ->orWhere('dv.CodigoSede', $sede);
            })
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
            $comision = Comision::find($codigo);

            $egreso = Egreso::find($comision->CodigoPagoComision);

            $paciente  = DB::table('comision as c')
            ->leftJoin('documentoventa as dv', 'dv.Codigo', '=', 'c.CodigoDocumentoVenta')
            ->leftJoin('contratoproducto as cp', 'cp.Codigo', '=', 'c.CodigoContrato')
            ->leftJoin('personas as pDV', 'pDV.Codigo', '=', 'dv.CodigoPaciente')
            ->leftJoin('personas as pCON', 'pCON.Codigo', '=', 'cp.CodigoPaciente')
            ->selectRaw("
                CASE 
                    WHEN dv.CodigoPaciente IS NOT NULL THEN CONCAT(pDV.Nombres, ' ', pDV.Apellidos)
                    WHEN cp.CodigoPaciente IS NOT NULL THEN CONCAT(pCON.Nombres, ' ', pCON.Apellidos)
                    ELSE 'No encontrado'
                END AS Paciente
            ")
            ->where('c.Codigo', $codigo)
            ->first();
        


            if ($comision) {
                return response()->json([
                    'comision' => $comision,
                    'egreso' => $egreso,
                    'paciente' => $paciente
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
                $query = DB::table('contratoproducto as cp')
                    ->join('personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                    ->leftJoin('comision as c', 'cp.Codigo', '=', 'c.CodigoContrato')
                    ->select([
                        'cp.Codigo as Codigo',
                        DB::raw("LPAD(cp.NumContrato, 5, '0') AS Documento"),
                        DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Paciente"),
                        DB::raw("DATE(cp.Fecha) as Fecha")
                    ])
                    ->where('cp.CodigoMedico', $medico)
                    ->where('cp.CodigoSede', $sede)
                    ->where('cp.Vigente', 1)
                    ->whereNull('c.Codigo')
                    ->where(function ($query) use ($termino) {
                        $query->where('p.Nombres', 'LIKE', "{$termino}%")
                              ->orWhere('p.Apellidos', 'LIKE', "{$termino}%");
                    });
                    
            }else{
                $query = DB::table('documentoventa as dv')
                ->join('personas as p', 'p.Codigo', '=', 'dv.CodigoPaciente')
                ->leftJoin('comision as c', 'dv.Codigo', '=', 'c.CodigoDocumentoVenta')
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
                ->whereNull('c.Codigo')
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
