<?php

namespace App\Http\Controllers\API\PagosVarios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagosVarios;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagosVariosController extends Controller
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


    public function registrarPagoVarios(Request $request)
    {
        $pagoVarios = $request->input('pagosVarios');
        $egreso = $request->input('egreso');
        //Validar Egreso
        $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();
        
        $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

        if($egreso['Monto'] > $total){
            return response()->json(['error' => 'No hay suficiente Efectivo en caja', 'Disponible' => $total ], 500);
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura de caja.'], 400);
        }


        DB::beginTransaction();
        try{

            $DataEgreso = Egreso::create($egreso);
            $idEgreso = $DataEgreso->Codigo;
            
            $pagoVarios['Codigo'] = $idEgreso;
            PagosVarios::create($pagoVarios);

            DB::commit();
            return response()->json(['message' => 'Pago Varios registrado correctamente'], 200);
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarPagosVarios(Request $request){
        
        $sede = $request->input('CodigoSede');
        $fechaInicio = $request->input('fechaInicio');
        $fechaFin = $request->input('fechaFin');
        $tipo = $request->input('tipo');

        try{
            $pagosVarios = DB::table('PagosVarios as pv')
            ->join('Egreso as e', 'e.Codigo', '=', 'pv.Codigo')
            ->join('Personas as p', 'p.Codigo', '=', 'pv.CodigoReceptor')
            ->join('Caja as c', 'c.Codigo', '=', 'e.CodigoCaja')
            ->selectRaw('e.Codigo, DATE(e.Fecha) as Fecha, pv.Tipo, e.Monto, pv.Comentario, CONCAT(p.Nombres, " ", p.Apellidos) as Receptor')
            ->where('e.Vigente', 1)
            ->where('c.CodigoSede', $sede)
            ->when(!empty($tipo), function ($query) use ($tipo) {
                return $query->where('pv.Tipo', $tipo);
            })
            ->when(!empty($fechaInicio), function ($query) use ($fechaInicio) {
                return $query->whereDate('e.Fecha', '>=', $fechaInicio);
            })
            ->when(!empty($fechaFin), function ($query) use ($fechaFin) {
                return $query->whereDate('e.Fecha', '<=', $fechaFin);
            })
            ->orderByDesc('e.Fecha')
            ->get();
        
        return response()->json($pagosVarios, 200);
            return response()->json($pagosVarios, 200);        
        }catch(\Exception $e){
            return response()->json(['error' => 'Error al listar los pagos varios', 'message' => $e->getMessage()], 500);
        }
    }

    public function consultarPagosVarios($codigo){
        try{
            // Obtener datos de pagosvarios
            $pagosVarios = DB::table('pagosvarios')
            ->select('CodigoReceptor', 'Tipo', 'Comentario', 'Motivo', 'Destino')
            ->where('Codigo', $codigo)
            ->first(); // Usamos first() para obtener un solo resultado

            // Obtener datos de egreso
            $egreso = DB::table('egreso')
            ->select('Monto', 'Fecha')
            ->where('Codigo', $codigo)
            ->first(); // Usamos first() para obtener un solo resultado

            // Retornar la respuesta en JSON
            return response()->json([
            'pagosVarios' => $pagosVarios,
            'egreso' => $egreso
            ]);
        }catch(\Exception $e){
            return response()->json(['error' => 'Error al consultar los pagos varios', 'message' => $e->getMessage()], 500);
        }
    }
}
