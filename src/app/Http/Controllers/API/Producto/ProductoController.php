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
use Illuminate\Support\Facades\Log;

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

            //log info
            Log::info('Productos listados correctamente', [
                'Controlador' => 'SedeController',
                'Metodo' => 'listarSedes',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'cantidad' => $productos->count()
            ]);


            return response()->json($productos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar productos', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarProductoCombo',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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


            //log info
            Log::info('Productos listados correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'cantidad' => $productos->count()
            ]);

            return response()->json($productos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar productos', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarProducto',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarProducto(RegistarProductoRequest $request)
    {
        $producto = $request->all();

        try {
            Producto::create($producto);
            //log info
            Log::info('Producto registrado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'registrarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Command' => $producto
            ]);

            return response()->json(['message' => 'Producto registrado correctamente'], 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al registrar producto', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'registrarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Command' => $producto
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarProducto(Request $request)
    {
        $producto = $request->all();
        try {
            Producto::where('Codigo', $producto['Codigo'])->update($producto);
            //log info
            Log::info('Producto actualizado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'actualizarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Command' => $producto
            ]);
            return response()->json(['message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al actualizar producto', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'actualizarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Command' => $producto
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarProducto($Codigo)
    {
        try {
            $producto = Producto::where('Codigo', $Codigo)->first();
            //log info
            Log::info('Producto consultado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'consultarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_producto' => $Codigo
            ]);
            return response()->json($producto, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar producto', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'consultarProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_producto' => $Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    //TEMPORAL

    public function listarTemporales($codigo)
    {
        try {
            $temporales = PrecioTemporal::where('CodigoProducto', $codigo)->get();

            //log info
            Log::info('Temporales listados correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_producto' => $codigo,
                'cantidad' => $temporales->count()
            ]);

            return response()->json($temporales, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar temporales', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_producto' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarTemporales(RegistrarTemporalRequest $request)
    {
        $producto = $request->input('temporal');

        try {
            PrecioTemporal::create($producto);
            //log info
            Log::info('Producto temporal registrado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'registrarTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Command' => $producto
            ]);
            return response()->json(['message' => 'Producto registrado correctamente'], 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al registrar producto temporal', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'registrarTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'Command' => $producto,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function actualizarTemporales(Request $request)
    {
        $producto = $request->all();
        try {

            // Verificar si el producto existe antes de actualizar
            $temporal = PrecioTemporal::findOrFail($producto['Codigo']);
            // Actualizar el producto siempre y cuando la cantidad actual sea igual al stock

            if ($temporal->Vigente == 0) {
                //log warning
                Log::warning('Intento de actualizar un precio temporal inactivo', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'actualizarTemporales',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo_temporal' => $producto['Codigo']
                ]);
                return response()->json(['error' => 'No se puede actualizar un precio temporal con estado inactivo.'], 400);
            }

            if ($temporal->Stock == $producto['Stock']) {
                PrecioTemporal::where('Codigo', $producto['Codigo'])->update($producto);

                //log info
                Log::info('Producto temporal actualizado correctamente', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'actualizarTemporales',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'Command' => $producto
                ]);

                return response()->json(['message' => 'Producto actualizado correctamente'], 200);
            } else {

                //log warning
                Log::warning('Intento de actualizar un precio temporal con cantidad diferente al stock', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'actualizarTemporales',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo_temporal' => $producto['Codigo'],
                    'cantidad_actual' => $temporal->Stock,
                    'cantidad_nueva' => $producto['Stock']
                ]);

                return response()->json(['error' => 'No se puede actualizar el precio temporal porque la cantidad actual no coincide con el stock.'], 400);
            }
        } catch (\Exception $e) {

            //log error
            Log::error('Error al actualizar producto temporal', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'actualizarTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_temporal' => $producto['Codigo'],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'Command' => $producto
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTemporal($Codigo)
    {
        try {
            $producto = PrecioTemporal::where('Codigo', $Codigo)->first();

            if (!$producto) {
                //log warning
                Log::warning('Producto temporal no encontrado', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'consultarTemporal',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo_temporal' => $Codigo
                ]);
                return response()->json(['error' => 'Producto temporal no encontrado'], 404);
            }

            return response()->json($producto, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar producto temporal', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'consultarTemporal',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_temporal' => $Codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function existePrecioTemporal($sede, $producto)
    {
        try {
            $existe = DB::table('preciotemporal')
                ->where('Vigente', 1)
                ->where('CodigoSede', $sede)
                ->where('CodigoProducto', $producto)
                ->where('Stock', '>', 0)
                ->exists();

            //log info
            Log::info('Consulta de existencia de precio temporal', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'existePrecioTemporal',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'producto' => $producto,
                'existe' => $existe
            ]);

            return response()->json(['existe' => $existe], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar existencia de precio temporal', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'existePrecioTemporal',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'producto' => $producto,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

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

            //log info
            Log::info('Precios temporales consultados correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'preciosTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'producto' => $producto,
                'cantidad' => $productos->count()
            ]);

            return response()->json($productos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar precios temporales', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'preciosTemporales',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'producto' => $producto,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

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

            if ($montoIGV === null) {
                //log warning
                Log::warning('No se encontró IGV para el combo', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'comboIGV',
                    'codigo_combo' => $codigo
                ]);
                return response()->json(['error' => 'No se encontró IGV para el combo'], 404);
            }

            //log info
            Log::info('IGV del combo consultado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'comboIGV',
                'codigo_combo' => $codigo,
                'monto_igv' => $montoIGV
            ]);

            return response()->json($montoIGV, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar IGV del combo', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'comboIGV',
                'codigo_combo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

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

            //log info
            Log::info('Combo de producto registrado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'registrarComboProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'combo' => $combo,
                'productos' => $productos
            ]);

            return response()->json(['message' => 'Producto registrado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al registrar combo de producto', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'registrarComboProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'combo' => $combo,
                'productos' => $productos,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

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

            //log info
            Log::info('Combo de producto actualizado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'actualizarComboProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'combo' => $combo,
                'productos' => $productos
            ]);

            return response()->json(['message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al actualizar combo de producto', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'actualizarComboProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'combo' => $combo,
                'productos' => $productos,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function consultarComboProducto($codigo)
    {

        try {
            // Consultar información del producto
            $producto = DB::table('producto')
                ->select('Codigo', 'Nombre', 'Descripcion', 'CodigoCategoria')
                ->where('Codigo', $codigo)
                ->first(); // Para obtener un solo resultado

            // Consultar productos dentro del combo
            $productosEnCombo = DB::table('productocombo as pc')
                ->join('producto as p', 'p.Codigo', '=', 'pc.CodigoProducto')
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

            $existe = DB::table('documentoventa as dv')
                ->join('detalledocumentoventa as ddv', 'dv.Codigo', '=', 'ddv.CodigoVenta')
                ->where('dv.Vigente', 1)
                ->where('ddv.CodigoProducto', $codigo)
                ->exists(); // Devuelve true si hay al menos un registro

            //log info
            Log::info('Combo de producto consultado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'consultarComboProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_combo' => $codigo,
                'producto' => $producto,
                'productos_en_combo' => $productosEnCombo->count(),
                'existe' => $existe
            ]);

            // Retornar en JSON
            return response()->json([
                'combo' => $producto,
                'productos' => $productosEnCombo,
                'existe' => $existe
            ]);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar combo de producto', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'consultarComboProducto',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo_combo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function listarCombos()
    {
        try {
            $productos = Producto::where('Tipo', 'C')->get();

            //log info
            Log::info('Combos listados correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarCombos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'cantidad' => $productos->count()
            ]);

            return response()->json($productos, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar combos', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'listarCombos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function precioCombo($sede, $combo)
    {

        try {

            $precioCombo = DB::table('productocombo')
                ->where('CodigoCombo', $combo)
                ->sum(DB::raw('Precio * Cantidad'));

            if ($precioCombo === null) {
                //log warning
                Log::warning('No se encontró precio para el combo', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'precioCombo',
                    'combo' => $combo
                ]);
                return response()->json(['error' => 'No se encontró precio para el combo'], 404);
            }

            //log info
            Log::info('Precio del combo consultado correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'precioCombo',
                'combo' => $combo,
                'precio_combo' => $precioCombo
            ]);

            return response()->json($precioCombo, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar precio del combo', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'precioCombo',
                'combo' => $combo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function tipoProductoCombo($producto)
    {
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

            if (!$resultado) {
                //log warning
                Log::warning('No se encontró información para el combo', [
                    'Controlador' => 'ProductoController',
                    'Metodo' => 'tipoProductoCombo',
                    'producto' => $producto
                ]);
            }

            //log info
            Log::info('Tipo de producto y monto IGV del combo consultados correctamente', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'tipoProductoCombo',
                'producto' => $producto,
                'resultado' => $resultado
            ]);

            return response()->json($resultado, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar tipo de producto y monto IGV del combo', [
                'Controlador' => 'ProductoController',
                'Metodo' => 'tipoProductoCombo',
                'producto' => $producto,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
