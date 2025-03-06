<?php

namespace App\Http\Controllers\API\Detraccion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Models\Recaudacion\Egreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
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
            $ventas = DB::table('documentoventa as dv')
                ->join('detraccion as d', 'dv.Codigo', '=', 'd.CodigoDocumentoVenta')
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

    public function registrarPagoDetraccion(Request $request)
    {
        $detraccion = $request->input('detraccion');
        $dataEgreso = $request->input('egreso');
        $empresa = $request->input('empresa');
        //Validar Egreso
        if(isset($dataEgreso['CodigoCuentaOrigen']) && $dataEgreso['CodigoCuentaOrigen'] == 0){
            $dataEgreso['CodigoCuentaOrigen'] = null;
        }

        if(isset($dataEgreso['CodigoBilleteraDigital']) && $dataEgreso['CodigoBilleteraDigital'] == 0){
            $dataEgreso['CodigoBilleteraDigital'] = null;
        }

        if ($dataEgreso['CodigoSUNAT'] == '008') {
            $dataEgreso['CodigoCuentaOrigen'] = null;
            $dataEgreso['CodigoBilleteraDigital'] = null;
            $dataEgreso['Lote'] = null;
            $dataEgreso['Referencia'] = null;
            $dataEgreso['NumeroOperacion'] = null;

            $total = MontoCaja::obtenerTotalCaja($dataEgreso['CodigoCaja']);

            if($dataEgreso['Monto'] > $total){
                return response()->json(['error' => 'No hay suficiente Efectivo en caja', 'Disponible' => $total ], 500);
            }

        }else if($dataEgreso['CodigoSUNAT'] == '003'){
            $dataEgreso['Lote'] = null;
            $dataEgreso['Referencia'] = null;

        }else if($dataEgreso['CodigoSUNAT'] == '005' || $dataEgreso['CodigoSUNAT'] == '006'){
            $dataEgreso['CodigoCuentaBancaria'] = null;
            $dataEgreso['CodigoBilleteraDigital'] = null;
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($dataEgreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaEgresoVal = Carbon::parse($dataEgreso['Fecha'])->toDateString(); // Convertir el string a Carbon

        
        if ($fechaCajaVal < $fechaEgresoVal) {
            return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
        }
        //Validar Egreso
        $egresoValidator = Validator::make($dataEgreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();
        DB::beginTransaction();
        try{

            $codigoEntidadBancaria = DB::table('cuentabancaria')
            ->where('Detraccion', 1)
            ->where('CodigoEmpresa', $empresa)
            ->value('CodigoEntidadBancaria');


            $egresoCreado = Egreso::create($dataEgreso)->Codigo;

            DB::table('pagodetraccion')->insert([
                'Codigo' => $egresoCreado,
                'CodigoCuentaDetraccion' =>  $codigoEntidadBancaria
            ]);

            DB::table('detraccion')
            ->whereIn('Codigo', $detraccion) // $detraccion es un array [1,2,3]
            ->update(['CodigoPagoDetraccion' => $egresoCreado]);
            DB::commit();
            return response()->json(['message' => 'Pago de Detracción registrado correctamente', 'egreso' => $egresoCreado]);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registrar el Pago de Detracción', 'bd' => $e->getMessage()], 500);
        }
    }
}
