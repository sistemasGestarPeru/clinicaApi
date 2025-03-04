<?php

namespace App\Http\Controllers\API\Producto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\RegistarProductoRequest;
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

    public function listarProductoCombo(Request $request)
    {
        $tipoProducto = $request->input('TipoProducto');
        try {

            $productos = DB::table('producto as p')
                ->join('categoriaproducto as cp', 'cp.Codigo', '=', 'p.CodigoCategoria')
                ->select(
                    'p.Codigo',
                    'cp.Nombre as Categoria',
                    'p.Nombre as Producto',
                    'p.Vigente',
                    DB::raw("CASE 
                    WHEN p.Tipo = 'S' THEN 'SERVICIO'
                    WHEN p.Tipo = 'B' THEN 'BIEN'
                    ELSE 'Desconocido'
                END AS Tipo")
                )
                ->where('p.Tipo', '!=', 'C')
                ->where('p.Tipo', $tipoProducto)
                ->where('p.Vigente', 1)
                ->orderBY('p.Nombre', 'ASC')
                ->get();

            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
                    'p.Vigente',
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

    public function listarTemporales($codigo)
    {
        try {
            $temporales = PrecioTemporal::where('CodigoProducto', $codigo)->get();
            return response()->json($temporales, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
    


    public function comboIGV($codigo)
    {
        try {
            $montoIGV = DB::table('productocombo as pc')
                ->join('sedeproducto as sd', 'sd.CodigoProducto', '=', 'pc.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sd.Codigotipogravado')
                ->where('pc.CodigoCombo', $codigo)
                ->selectRaw("SUM(CASE WHEN tg.Tipo = 'G' THEN (pc.Precio - (pc.Precio / (1 + (tg.Porcentaje / 100)))) * pc.Cantidad ELSE 0 END) AS MontoIGV")
                ->value('MontoIGV'); // Obtiene el resultado directamente

            return response()->json($montoIGV, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

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
        $combo = $request->input('combo');
        $productos = $request->input('productos');
        DB::beginTransaction();
        try {
            // Actualizar la información del combo
            Producto::where('Codigo', $combo['Codigo'])->update($combo);

            // Obtener productos actuales del combo en la base de datos
            $productosActuales = ComboProducto::where('CodigoCombo', $combo['Codigo'])->get()->keyBy('CodigoProducto');

            // Crear un array con los códigos de productos nuevos
            $nuevosCodigos = collect($productos)->pluck('CodigoProducto')->toArray();

            // Eliminar productos que ya no están en la nueva lista
            foreach ($productosActuales as $codigoProducto => $producto) {
                if (!in_array($codigoProducto, $nuevosCodigos)) {
                    $producto->delete();
                }
            }

            // Insertar o actualizar los productos nuevos
            foreach ($productos as $producto) {
                $producto['CodigoCombo'] = $combo['Codigo'];
                
                if (isset($productosActuales[$producto['CodigoProducto']])) {
                    // Producto ya existe, verificamos si cambió algún dato
                    $productoExistente = $productosActuales[$producto['CodigoProducto']];
                    
                    if (
                        $productoExistente->Cantidad != $producto['Cantidad'] ||
                        $productoExistente->Precio != $producto['Precio'] ||
                        $productoExistente->Vigente != $producto['Vigente']
                    ) {
                        // Actualizar si hay cambios
                        $productoExistente->update([
                            'Cantidad' => $producto['Cantidad'],
                            'Precio' => $producto['Precio'],
                            'Vigente' => $producto['Vigente']
                        ]);
                    }
                } else {
                    // Producto no existe, se inserta
                    ComboProducto::create($producto);
                }
            }
            DB::commit();
            return response()->json(['message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function consultarComboProducto($codigo)
    {
        
        try {
            // Consultar información del producto
            $producto = DB::table('Producto')
                ->select('Codigo', 'Nombre', 'Descripcion', 'CodigoCategoria')
                ->where('Codigo', $codigo)
                ->first(); // Para obtener un solo resultado
        
            // Consultar productos dentro del combo
            $productosEnCombo = DB::table('productocombo as pc')
                ->join('Producto as p', 'p.Codigo', '=', 'pc.CodigoProducto')
                ->select(
                    'p.Codigo',
                    'p.Nombre as Producto',
                    'p.Vigente',
                    'pc.Cantidad',
                    'pc.Precio',
                    DB::raw("CASE 
                                WHEN p.Tipo = 'S' THEN 'SERVICIO' 
                                WHEN p.Tipo = 'B' THEN 'BIEN' 
                                ELSE 'Desconocido' 
                            END AS Tipo")
                )
                ->where('pc.CodigoCombo', $codigo)
                ->orderBy('p.Nombre', 'ASC')
                ->get(); // Para obtener múltiples resultados
        
            // Retornar en JSON
            return response()->json([
                'combo' => $producto,
                'productos' => $productosEnCombo
            ]);
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

    public function precioCombo($sede, $combo){

        try{

            $precioCombo = DB::table('productocombo')
            ->where('CodigoCombo', $combo)
            ->sum(DB::raw('Precio * Cantidad'));

            return response()->json($precioCombo, 200);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function tipoProductoCombo($producto){
        try {
            $resultado = DB::table('productocombo as pc')
                ->join('sedeproducto as sd', 'sd.CodigoProducto', '=', 'pc.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sd.Codigotipogravado')
                ->join('producto as p', 'p.Codigo', '=', 'pc.CodigoProducto')
                ->where('pc.CodigoCombo', $producto)
                ->selectRaw("
                    SUM(CASE 
                        WHEN tg.Tipo = 'G' THEN 
                            (pc.Precio - (pc.Precio / (1 + (tg.Porcentaje / 100)))) * pc.Cantidad 
                        ELSE 0 
                    END) AS MontoIGV,
                    CASE 
                        WHEN COUNT(DISTINCT p.Tipo) = 1 THEN MAX(p.Tipo)  
                        ELSE 'Mixto' 
                    END AS TipoResultado
                ")
                ->first(); // Obtiene una sola fila con ambos valores
    
            return response()->json($resultado, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
