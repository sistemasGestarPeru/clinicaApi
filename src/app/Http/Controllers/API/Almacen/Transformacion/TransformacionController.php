<?php

namespace App\Http\Controllers\API\Almacen\Transformacion;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaIngreso\DetalleGuiaIngreso;
use App\Models\Almacen\GuiaIngreso\GuiaIngreso;
use App\Models\Almacen\GuiaSalida\DetalleGuiaSalida;
use App\Models\Almacen\GuiaSalida\GuiaSalida;
use App\Models\Almacen\Lote\Lote;
use App\Models\Almacen\Lote\MovimientoLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function listarTransformaciones(Request $request)
    {

        try {
            $lotes = DB::table('guiaingreso as gi')
                ->select([
                    'l.FechaCaducidad',
                    'p.Nombre AS ProductoDestino',
                    'l.Cantidad AS CantidadDestino',
                    'l.Stock as StockDestino',
                    'gi.Fecha as Creacion',
                ])
                ->join('detalleguiaingreso as dgi', 'gi.Codigo', '=', 'dgi.CodigoGuiaRemision')
                ->join('lote as l', 'dgi.Codigo', '=', 'l.CodigoDetalleIngreso')
                ->join('producto as p', 'dgi.CodigoProducto', '=', 'p.Codigo')
                ->where('gi.TipoDocumento', 'T')
                ->where('gi.CodigoSede', $request->input('Sede'))
                ->where('gi.Vigente', 1)
                ->get();

            // Log de éxito
            Log::info('Transformaciones listadas correctamente', [
                'Controlador' => 'TransformacionController',
                'Metodo' => 'listarTransformaciones',
                'cantidad' => count($lotes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($lotes, 200);
        } catch (\Exception $e) {
            // Log del error
            Log::error('Error al listar transformaciones', [
                'Controlador' => 'TransformacionController',
                'Metodo' => 'listarTransformaciones',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'error' => 'Error al listar las transformaciones',
                'bd' => $e->getMessage()
            ], 500);
        }
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
                ->where('p.Vigente', 1)
                ->where('sp.CodigoSede', $sede)
                ->orderBy('p.Nombre', 'asc')
                ->get();

            // Log de éxito
            Log::info('Usuarios listados correctamente', [
                'Controlador' => 'TransformacionController',
                'Metodo' => 'listarProductosDisponibles',
                'cantidad' => count($productos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($productos, 200);
        } catch (\Exception $e) {


            // Log del error general
            Log::error('Error inesperado al listar Productos', [
                'Controlador' => 'TransformacionController',
                'Metodo' => 'listarProductosDisponibles',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'error' => 'Error al listar los productos disponibles',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarTransformacion(Request $request)
    {
        $fecha = date('Y-m-d');
        $transformacion = $request->input('transformacion');
        $lote = $request->input('lote');
        $fechaActual = date('Y-m-d H:i:s');

        DB::beginTransaction();
        try {
            /***************************** TRANSFORMACION SALIDA **************************/

            //Consultar el stock en la sede (ORIGEN)
            $productoOrigen = DB::table('sedeproducto')
                ->where('CodigoProducto', $transformacion['ProductoOrigen'])
                ->where('CodigoSede', $transformacion['CodigoSede'])
                ->first();

            //Para calcular el nuevo stock del lote
            $stockSedeOrigen = $productoOrigen->Stock ?? 0;
            $costoSedeOrigen = $productoOrigen->CostoCompraPromedio ?? 0;

            DB::table('lote')->where('Codigo', $lote['Codigo'])->decrement('Stock', $transformacion['CantidadOrigen']);
            $stockSedeOrigen -= $transformacion['CantidadOrigen'];

            //Generar Salida (ORIGEN)
            $guiaSalida['CodigoSede'] = $transformacion['CodigoSede'];
            $guiaSalida['CodigoTrabajador'] = $transformacion['CodigoTrabajador'];
            $guiaSalida['TipoDocumento'] = 'T';
            $guiaSalida['Serie'] = 'T'; // CAMBIAR
            $guiaSalida['Numero'] = $lote['Codigo']; // CAMBIAR
            $guiaSalida['Fecha'] = $fecha;
            $guiaSalida['Motivo'] = 'T';

            $guiaSalidaCreada = GuiaSalida::create($guiaSalida);

            //Generar Detalle Salida (ORIGEN)
            $detalleGuiaSalida['Cantidad'] = $transformacion['CantidadOrigen'];
            $detalleGuiaSalida['Costo'] = $lote['Costo']; // verificar 
            $detalleGuiaSalida['CodigoGuiaSalida'] = $guiaSalidaCreada->Codigo;
            $detalleGuiaSalida['CodigoProducto'] = $transformacion['ProductoOrigen'];
            $detalleGuiaSalida = DetalleGuiaSalida::create($detalleGuiaSalida);

            //Generar Movimiento LOTE (ORIGEN)

            $movimientoLoteOrigen['CodigoDetalleSalida'] = $detalleGuiaSalida->Codigo;
            $movimientoLoteOrigen['CodigoLote'] = $lote['Codigo'];
            $movimientoLoteOrigen['TipoOperacion'] = 'O';
            $movimientoLoteOrigen['Fecha'] = $fechaActual;
            $movimientoLoteOrigen['Stock'] = $stockSedeOrigen;
            $movimientoLoteOrigen['Cantidad'] = $transformacion['CantidadOrigen'];
            $movimientoLoteOrigen['CostoPromedio'] = $costoSedeOrigen;

            MovimientoLote::create($movimientoLoteOrigen);


            //Actualizar el stock de la sede Origen
            DB::table('sedeproducto')
                ->where('CodigoProducto', $transformacion['ProductoOrigen'])
                ->where('CodigoSede', $transformacion['CodigoSede'])
                ->decrement('Stock', $transformacion['CantidadOrigen']);

            /***************************** TRANSFORMACION ENTRADA **************************/

            $productoDestino = DB::table('sedeproducto')
                ->where('CodigoProducto', $transformacion['ProductoDestino'])
                ->where('CodigoSede', $transformacion['CodigoSede'])
                ->first();

            $stockSedeDestino = $productoDestino->Stock ?? 0;
            $costoSedeDestino = $productoDestino->CostoCompraPromedio ?? 0;
            $inversionSedeDestino = $stockSedeDestino * $costoSedeDestino;

            $inversionLoteDestino = $transformacion['CostoU'] * $transformacion['CantidadDestino'];
            $nuevoStockDestino = $transformacion['CantidadDestino'] + $stockSedeDestino;
            $nuevaInversionDestino = $inversionSedeDestino + $inversionLoteDestino;
            $nuevoCostoDestino = $nuevaInversionDestino / $nuevoStockDestino;

            //Generar Ingreso (DESTINO)
            $guiaIngreso['CodigoSede'] = $transformacion['CodigoSede'];
            $guiaIngreso['CodigoTrabajador'] = $transformacion['CodigoTrabajador'];
            $guiaIngreso['TipoDocumento'] = 'T';
            $guiaIngreso['Serie'] = 'S123'; // CAMBIAR
            $guiaIngreso['Numero'] = 123; // CAMBIAR
            $guiaIngreso['Fecha'] = $fecha;
            $guiaIngreso['Motivo'] = 'T';

            $guiaIngresoCreada = GuiaIngreso::create($guiaIngreso);

            //Generar Detalle Ingreso (DESTINO)
            $detalleGuiaIngreso['Cantidad'] = $transformacion['CantidadDestino'];
            $detalleGuiaIngreso['Costo'] = $transformacion['CostoU']; // verificar 
            $detalleGuiaIngreso['CodigoGuiaRemision'] = $guiaIngresoCreada->Codigo;
            $detalleGuiaIngreso['CodigoProducto'] = $transformacion['ProductoDestino'];
            $detalleGuiaIngreso = DetalleGuiaIngreso::create($detalleGuiaIngreso);

            //Generar LOTE (DESTINO)

            $loteDestino['Serie'] = '123'; // CAMBIAR
            $loteDestino['Cantidad'] = $transformacion['CantidadDestino']; // CAMBIAR
            $loteDestino['Stock'] = $transformacion['CantidadDestino']; // CAMBIAR
            $loteDestino['Costo'] = $transformacion['CostoU'];
            $loteDestino['MontoIGV'] = 123; // CAMBIAR
            $loteDestino['FechaCaducidad'] = $lote['FechaCaducidad'];
            $loteDestino['CodigoProducto'] = $transformacion['ProductoDestino'];
            $loteDestino['CodigoSede'] = $transformacion['CodigoSede'];
            $loteDestino['CodigoDetalleIngreso'] = $detalleGuiaIngreso->Codigo;
            $loteCreado = Lote::create($loteDestino);

            //Generar Movimiento LOTE (DESTINO)
            $movimientoLoteDestino['CodigoDetalleIngreso'] = $detalleGuiaIngreso->Codigo;
            $movimientoLoteDestino['CodigoLote'] = $loteCreado->Codigo;
            $movimientoLoteDestino['Cantidad'] = $transformacion['CantidadDestino'];
            $movimientoLoteDestino['Stock'] = $nuevoStockDestino;
            $movimientoLoteDestino['CostoPromedio'] = $nuevoCostoDestino;
            $movimientoLoteDestino['Fecha'] = $fechaActual;
            $movimientoLoteDestino['TipoOperacion'] = 'D';

            MovimientoLote::create($movimientoLoteDestino);

            //Actualizar el stock de la sede Destino
            DB::table('sedeproducto')
                ->where('CodigoProducto', $transformacion['ProductoOrigen'])
                ->where('CodigoSede', $transformacion['CodigoSede'])
                ->update([
                    'CostoCompraPromedio' => $nuevoCostoDestino,
                    'Stock' => $nuevoStockDestino
                ]);

            DB::commit();
            // Log de éxito
            Log::info('Transformación registrada correctamente', [
                'Controlador' => 'TransformacionController',
                'Metodo' => 'registrarTransformacion',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'transformacion' => $transformacion,
                'lote' => $lote
            ]);
            return response()->json([
                'success' => 'Transformación registrada correctamente',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error
            Log::error('Error al registrar la transformación', [
                'Controlador' => 'TransformacionController',
                'Metodo' => 'registrarTransformacion',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'error' => 'Error al registrar la transformación',
                'bd' => $e->getMessage()
            ], 500);
        }
    }
}
