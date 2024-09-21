<?php

namespace App\Http\Controllers\API\ContratoProducto;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\ContratoProducto;
use App\Models\Recaudacion\DetalleContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ContratoProductoeController extends Controller
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

    public function buscarProducto(Request $request)
    {
        $nombreProducto = $request->input('nombreProducto');
        $sede = $request->input('sede');



        // $producto = DB::table('clinica_db.producto')
        // ->where('Vigente', 1)
        // ->where('Nombre', 'like', '%' . $nombreProducto . '%')
        // ->select('Codigo as Codigo', 'Nombre as Nombre', 'Monto as Monto', 'Tipo as Tipo', 'TipoGravado as TipoGravado')
        // ->get();



        try {
            $producto = DB::table('sedeproducto as sp')
                ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->select('p.Codigo', 'p.Nombre', 'p.Monto', 'p.Tipo', 'p.TipoGravado')
                ->where('p.Vigente', 1)
                ->where('p.Nombre', 'like', '%' . $nombreProducto . '%')
                ->where('sp.CodigoSede', $sede)
                ->get();

            return response()->json($producto);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function consultarDeuda(Request $request)
    {
        $codigoContrato = $request->input('codigoContrato');

        try {

            $estadoPago = DB::table('contratoproducto as cp')
                ->leftJoin('documentoventa as dv', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
                ->select(DB::raw("
                CASE 
                    WHEN SUM(CASE WHEN dv.Vigente = 1 THEN dv.MontoPagado ELSE 0 END) IS NULL THEN cp.Total
                    WHEN SUM(CASE WHEN dv.Vigente = 1 THEN dv.MontoPagado ELSE 0 END) = cp.Total THEN 0
                    ELSE cp.Total - SUM(CASE WHEN dv.Vigente = 1 THEN dv.MontoPagado ELSE 0 END)
                END AS EstadoPago
            "))
                ->where('cp.Codigo', $codigoContrato)
                ->groupBy('cp.Total')
                ->first();

            return response()->json($estadoPago);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarContratoProducto(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesContrato = $request->input('detalleContrato');
        $contratoProductoData = $request->input('contratoProducto');

        if (isset($contratoProductoData['CodigoPaciente']) && $contratoProductoData['CodigoPaciente'] == 0) {
            $contratoProductoData['CodigoPaciente'] = null;
        }

        if (isset($contratoProductoData['CodigoClienteEmpresa']) && $contratoProductoData['CodigoClienteEmpresa'] == 0) {
            $contratoProductoData['CodigoClienteEmpresa'] = null;
        }

        // Obtener el CódigoSede desde los datos del contrato
        $codigoSede = $contratoProductoData['CodigoSede'];

        // Obtener el último NumContrato para la sede específica y sumarle 1
        $ultimoNumContrato = ContratoProducto::where('CodigoSede', $codigoSede)
            ->max('NumContrato');
        $contratoProductoData['NumContrato'] = $ultimoNumContrato ? $ultimoNumContrato + 1 : 1;

        $contratoProductoData['Fecha'] = $fecha;

        DB::beginTransaction();
        try {
            // Crear el ContratoProducto
            $Contrato = ContratoProducto::create($contratoProductoData);

            // Crear los DetalleContrato
            foreach ($detallesContrato as $detalle) {
                $detalle['CodigoContrato'] = $Contrato->Codigo;
                DetalleContrato::create($detalle);
            }

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'message' => 'Contrato registrado correctamente'
            ], 200);
        } catch (\Exception $e) {
            // Hacer rollback en caso de error
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buscarContratoProducto(Request $request)
    {
        $fecha = $request->input('fecha');
        $codigoSede = $request->input('codigoSede');

        try {
            $contratos = DB::table('clinica_db.contratoproducto as cp')
                ->leftJoin('clinica_db.personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                ->leftJoin('clinica_db.clienteempresa as ce', 'ce.Codigo', '=', 'cp.CodigoClienteEmpresa')
                ->where(DB::raw("DATE(cp.Fecha)"), $fecha)
                ->where('cp.CodigoSede', $codigoSede)
                ->where('cp.Vigente', 1)
                ->select(
                    'cp.Codigo as Codigo',
                    DB::raw("DATE(cp.Fecha) as Fecha"),
                    'cp.Total as Total',
                    DB::raw("
                        CASE 
                            WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                            WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                            ELSE 'No identificado'
                        END as NombrePaciente")
                )
                ->get();

            return response()->json($contratos);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function anularContrato(Request $request)
    {
        $codigo = $request->input('codigoContrato');

        try {
            // Verificar si no hay ventas asociadas
            $documentoVenta = DB::table('documentoventa as dv')
                ->leftJoin('contratoproducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
                ->where('cp.Codigo', $codigo)
                ->select('dv.Codigo')
                ->first();

            // Si no hay ventas asociadas, actualizar el contrato
            if ($documentoVenta === null) {
                DB::table('contratoproducto')
                    ->where('Codigo', $codigo)
                    ->update(['Vigente' => 0]);

                return response()->json([
                    'message' => 'Contrato anulado correctamente',
                    'id' => 1
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No se puede anular el contrato porque tiene ventas asociadas',
                    'id' => 2
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
