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

        try {
            $producto = DB::table('clinica_db.producto')
                ->where('Vigente', 1)
                ->where('Nombre', 'like', '%' . $nombreProducto . '%')
                ->select('Codigo as Codigo', 'Nombre as Nombre', 'Monto as Monto', 'Tipo as Tipo', 'TipoGravado as TipoGravado')
                ->get();

            return response()->json($producto);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarContratoProducto(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesContrato = $request->input('detalleContrato');
        $contratoProductoData = $request->input('contratoProducto');
        $contratoProductoData['Fecha'] = $fecha;
        //revisar como trae la data
        DB::beginTransaction();
        try {
            // Crear el ContratoProducto
            ContratoProducto::create($contratoProductoData);

            $ultimoRegistroContratoP = ContratoProducto::orderBy('Codigo', 'desc')->first();
            // Crear los DetalleContrato
            foreach ($detallesContrato as $detalle) {
                $detalle['CodigoContrato'] = $ultimoRegistroContratoP['Codigo'];
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

        try {
            $contrato = DB::table('clinica_db.contratoproducto as cp')
                ->join('clinica_db.personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                ->where(DB::raw("DATE(cp.Fecha)"), $fecha)
                ->where('cp.Vigente', 1)
                ->select('cp.Codigo as Codigo', DB::raw("DATE(cp.Fecha) as Fecha"), 'cp.Total as Total', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as NombrePaciente"))
                ->get();

            return response()->json($contrato);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
