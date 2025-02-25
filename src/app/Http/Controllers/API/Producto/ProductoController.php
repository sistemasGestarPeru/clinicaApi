<?php

namespace App\Http\Controllers\API\Producto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\RegistarProductoRequest;
use App\Http\Requests\Producto\RegistrarProductoComboRequest;
use App\Http\Requests\Producto\RegistrarTemporalRequest;
use App\Models\Recaudacion\ComboProducto;
use App\Models\Recaudacion\PrecioTemporal;
use App\Models\Recaudacion\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
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


    public function listarProducto(Request $request)
    {
        try {

            $productos = DB::table('producto as p')
                ->join('categoriaproducto as cp', 'cp.Codigo', '=', 'p.CodigoCategoria')
                ->select(
                    'p.Codigo',
                    'cp.Nombre as Categoria',
                    'p.Nombre as Producto',
                    DB::raw("CASE 
                    WHEN p.Tipo = 'S' THEN 'SERVICIO'
                    WHEN p.Tipo = 'B' THEN 'BIEN'
                    ELSE 'Desconocido'
                END AS Tipo")
                )
                ->where('p.Tipo', '!=', 'C')
                ->orderBY('p.Nombre', 'ASC')
                ->get();

            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarProducto(RegistarProductoRequest $request)
    {
        $producto = $request->all();

        try {
            Producto::create($producto);
            return response()->json(['message' => 'Producto registrado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarProducto(Request $request)
    {
        $producto = $request->all();
        try {
            Producto::where('Codigo', $producto['Codigo'])->update($producto);
            return response()->json(['message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarProducto($Codigo)
    {
        try {
            $producto = Producto::where('Codigo', $Codigo)->first();
            return response()->json($producto, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    //TEMPORAL
    public function registrarTemporales(RegistrarTemporalRequest $request)
    {
        $producto = $request->input('temporal');
        PrecioTemporal::create($producto);
        return response()->json(['message' => 'Producto registrado correctamente'], 200);
        try {
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarTemporales(Request $request)
    {
        $producto = $request->all();
        try {
            PrecioTemporal::where('Codigo', $producto['Codigo'])->update($producto);
            return response()->json(['message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTemporal($Codigo)
    {
        try {
            $producto = PrecioTemporal::where('Codigo', $Codigo)->first();
            return response()->json($producto, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function preciosTemporales($sede, $producto)
    {
        try {
            $productos = DB::table('preciotemporal')
                ->select('Codigo AS Temporal', 'Precio', 'Stock')
                ->where('Vigente', 1)
                ->where('CodigoSede', $sede)
                ->where('CodigoProducto', $producto)
                ->where('Stock', '>', 0)
                ->get();
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //COMBO PRODUCTOS

    public function registrarComboProducto(Request $request)
    {
        $combo = $request->input('combo');
        $productos = $request->input('productos');

        DB::beginTransaction();
        try {

            $codProducto = Producto::create($combo)->Codigo;

            
            foreach ($productos as $producto) {
                $producto['CodigoCombo'] = $codProducto;
                ComboProducto::create($producto);
            }
            DB::commit();
            return response()->json(['message' => 'Producto registrado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarComboProducto(Request $request)
    {
        $producto = $request->all();
        try {
            ComboProducto::where('Codigo', $producto['Codigo'])->update($producto);
            return response()->json(['message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarComboProducto($Codigo)
    {
        try {
            $producto = ComboProducto::where('Codigo', $Codigo)->first();
            return response()->json($producto, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarCombos(){
        try{
            $productos = Producto::where('Tipo', 'C')->get();
            return response()->json($productos, 200);
        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
