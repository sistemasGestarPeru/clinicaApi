<?php

namespace App\Http\Controllers\API\Almacen\Transformacion;

use App\Http\Controllers\Controller;
use App\Models\Almacen\Lote\Lote;
use App\Models\Almacen\Lote\MovimientoLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransformacionController extends Controller
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

    public function listarProductosDisponibles($sede)
    {
        try {
            $productos = DB::table('sedeproducto as sp')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->select('sp.CodigoProducto', 'p.Nombre', 'sp.Stock', 'sp.CostoCompraPromedio')
                ->where('p.Tipo', 'B')
                // ->where('sp.Stock', '>', 0)
                ->where('sp.Vigente', 1)
                ->where('sp.CodigoSede', $sede)
                ->get();
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al listar los productos disponibles',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarTransformacion(Request $request)
    {
        $fecha = date('Y-m-d');
        $data = $request->all();
        DB::beginTransaction();

        $sedeProducto = DB::table('sedeproducto')
            ->where('CodigoProducto', $data['ProductoOrigen'])
            ->where('CodigoSede', $data['CodigoSede'])
            ->first();

        //Para calcular el nuevo stock del lote
        $costoSede = $sedeProducto->CostoCompraPromedio ?? 0;

        try {

            foreach ($data['lote'] as $detalle) {
                //Transformación de producto ORIGEN (SALIDA)

                //Stock Actual
                $stockActual = DB::table('lote')->where('Codigo', $detalle['Codigo'])->value('Stock');
                //Stock Nuevo
                DB::table('lote')->where('Codigo', $detalle['Codigo'])->decrement('Stock', $detalle['Cantidad']);

                //PARA GENERAR MOVIMIENTO LOTE

                $movimientoLote['CodigoLote'] = $detalle['Codigo'];
                $movimientoLote['TipoOperacion'] = 'T';
                $movimientoLote['Fecha'] = $fecha;
                $movimientoLote['Cantidad'] = $stockActual - $detalle['Cantidad'];
                $movimientoLote['Stock'] = $stockActual - $detalle['Cantidad'];
                $movimientoLote['CostoPromedio'] = $costoSede;

                MovimientoLote::create($movimientoLote);
            }

            //Actualizar el stock de la sede
            DB::table('sedeproducto')
                ->where('CodigoProducto', $data['ProductoOrigen'])
                ->where('CodigoSede', $data['CodigoSede'])
                ->decrement('Stock', $data['CantidadOrigen']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transformación registrada correctamente'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al registrar la transformación',
                'bd' => $e->getMessage()
            ], 500);
        }
    }
}
