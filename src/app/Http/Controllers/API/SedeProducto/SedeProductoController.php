<?php

namespace App\Http\Controllers\API\SedeProducto;

use App\Http\Controllers\Controller;
use App\Http\Requests\SedeProducto\RegistrarSedeProductoRequest;
use App\Models\Recaudacion\SedeProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SedeProductoController extends Controller
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

    public function listarProductosNoAsignados(){
        
        try{

            $productos = DB::table('producto as p')
            ->leftJoin('sedeproducto as sp', 'p.Codigo', '=', 'sp.CodigoProducto')
            ->select(
                'p.Codigo as CodigoProducto',
                'p.Nombre',
                DB::raw("
                    CASE
                        WHEN p.Tipo = 'S' THEN 'SERVICIO'
                        WHEN p.Tipo = 'B' THEN 'BIEN'
                    END AS TipoProducto
                ")
            )
            ->where('p.Vigente', 1)
            ->whereNull('sp.Codigo')
            ->get();

            return response()->json($productos, 200);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarSedeProducto($sede){
        try{

            $productos = DB::table('producto as p')
            ->join('sedeproducto as sp', 'p.Codigo', '=', 'sp.CodigoProducto')
            ->join('tipogravado as tg', 'sp.CodigoTipoGravado', '=', 'tg.Codigo')
            ->join('sedesrec as s', 's.Codigo', '=', 'sp.CodigoSede')
            ->select(
                'sp.Codigo as CodProdSede',
                'p.Codigo as CodProd',
                's.Nombre as Sede',
                'p.Nombre as Producto',
                'sp.Precio',
                'sp.Stock',
                'tg.Tipo as TipoGravado'
            )
            ->where('p.Vigente', 1)
            ->where('sp.CodigoSede', $sede)
            ->get();

            return response()->json($productos, 200);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarProductoSede(Request $request){
        $sedeProductos = $request->input('sedeProductos');
        try{
            
            $dataValidar = Validator::make(
                ['sedeProductos' => $sedeProductos],
                (new RegistrarSedeProductoRequest())->rules(),
            );
            $dataValidar->validate();

            //Registrar Detalle

            foreach($sedeProductos as $sedeProducto){
                SedeProducto::create($sedeProducto);
            }

            return response()->json(['message' => 'Productos registrados correctamente en la sede'], 200);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
