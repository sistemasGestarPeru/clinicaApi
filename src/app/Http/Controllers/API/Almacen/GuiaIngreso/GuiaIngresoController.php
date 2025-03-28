<?php

namespace App\Http\Controllers\API\Almacen\GuiaIngreso;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaIngreso\DetalleGuiaIngreso;
use App\Models\Almacen\GuiaIngreso\GuiaIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuiaIngresoController extends Controller
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

    public function listarGuiaIngreso(Request $request){
        $filtros = $request->all();
        try{
            
            $guiaIngreso = GuiaIngreso::all()->where('CodigoSede', $filtros['CodigoSede']);

            return response()->json($guiaIngreso, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar las guias de ingreso' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function listarComprasActivas($sede){
        
        try{
            $compras = DB::table('compra as c')
            ->select([
                'c.Codigo',
                DB::raw("CONCAT(c.Serie, ' - ', LPAD(c.Numero, 4, '0')) as Descripcion")
            ])
            ->where('c.CodigoSede', $sede)
            ->where('c.Vigente', 1)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('detallecompra as dc')
                    ->join('producto as p', 'p.Codigo', '=', 'dc.CodigoProducto')
                    ->leftJoinSub(
                        DB::table('guiaingreso as gi')
                            ->join('detalleguiaingreso as dgi', 'dgi.CodigoGuiaRemision', '=', 'gi.Codigo')
                            ->select('dgi.CodigoProducto', DB::raw('SUM(dgi.Cantidad) as Cantidad'))
                            ->whereColumn('gi.CodigoCompra', 'c.Codigo')  // Equivalente a gi.CodigoCompra = c.Codigo
                            ->groupBy('dgi.CodigoProducto'),
                        'Entregado',
                        'Entregado.CodigoProducto',
                        '=',
                        'dc.CodigoProducto'
                    )
                    ->whereColumn('dc.CodigoCompra', 'c.Codigo')
                    ->whereRaw('(dc.Cantidad - COALESCE(Entregado.Cantidad, 0)) > 0')
                    ->where('p.Tipo', 'B');
            })
            ->get();
        
            return response()->json($compras, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar las Compras' ,'bd' => $e->getMessage()], 500);
        }
    }

    public function listarDetalleCompra($compra){
        try{
            $detalleCompra = DB::table('detallecompra as dc')
                ->leftJoinSub(
                    DB::table('guiaingreso as gi')
                        ->join('detalleguiaingreso as dgi', 'dgi.CodigoGuiaRemision', '=', 'gi.Codigo')
                        ->where('gi.CodigoCompra', $compra)
                        ->groupBy('dgi.CodigoProducto')
                        ->select(
                            'dgi.CodigoProducto',
                            DB::raw('SUM(dgi.Cantidad) as Cantidad')
                        ),
                    'Entregado',
                    'Entregado.CodigoProducto',
                    '=',
                    'dc.CodigoProducto'
                )
                ->where('dc.CodigoCompra', $compra)
                ->whereRaw('(DC.Cantidad - COALESCE(Entregado.Cantidad, 0)) > 0')
                ->select([
                    DB::raw('DC.Cantidad - COALESCE(Entregado.Cantidad, 0) as Cantidad'),
                    'DC.CodigoProducto',
                    'DC.Descripcion',
                    DB::raw('((DC.MontoTotal - DC.MontoIGV))/(DC.Cantidad - COALESCE(Entregado.Cantidad, 0)) AS Costo'),
                ])
                ->get();

            return response()->json($detalleCompra, 200);
        }catch(\Exception $e){
            return response()->json(['error' => 'Ocurrió un error al listar los detalles de la compra' ,'bd' => $e->getMessage()], 500);
        }
    }


    public function registrarGuiaIngreso(Request $request)
    {
        $guiaData = $request->input('guiaIngreso');
        $detalleGuia = $request->input('detalleGuiaIngreso');
        DB::beginTransaction();

        try{

            $guiaIngreso = GuiaIngreso::create($guiaData);

            foreach($detalleGuia as $detalle){
                $detalle['CodigoGuiaRemision'] = $guiaIngreso->Codigo;
                DetalleGuiaIngreso::create($detalle);
            }
            

            DB::commit();
            return response()->json(['message' => 'Guia de Ingreso registrada correctamente', 'Codigo' => $guiaIngreso->Codigo], 200);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registar la Guia de Ingreso' ,'bd' => $e->getMessage()], 500);
        }

    }

}
