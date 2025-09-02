<?php

namespace App\Http\Controllers\API\Almacen\Lote;

use App\Http\Controllers\Controller;
use App\Models\Almacen\Lote\Lote;
use App\Models\Almacen\Lote\MovimientoLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoteController extends Controller
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

    public function listarLotes(Request $request)
    {
        $data = $request->all();
        try {
            $lotes = DB::table('guiaingreso as GI')
                ->join('detalleguiaingreso as DGI', 'GI.Codigo', '=', 'DGI.CodigoGuiaRemision')
                ->join('lote as L', 'DGI.Codigo', '=', 'L.CodigoDetalleIngreso')
                ->join('producto as P', 'P.Codigo', '=', 'L.CodigoProducto')
                ->where('L.CodigoSede', $data['sede'])
                // ->where('GI.Vigente', 1)
                ->select([
                    'L.Codigo',
                    'GI.Fecha as FechaIngreso',
                    'L.FechaCaducidad as FechaCaducidad',
                    'L.Serie',
                    'P.Nombre',
                    'L.Cantidad',
                    'L.Stock',
                    'L.Vigente'
                ])
                ->get();

            // Log de éxito
            Log::info('Lotes listados correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'listarLotes',
                'cantidad' => count($lotes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($lotes, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Ocurrió un error al listar Lotes', [
                'Controlador' => 'LoteController',
                'Metodo' => 'listarLotes',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al listar Lotes', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarGuiasIngreso($sede)
    {
        try {

            $resultados = DB::table('guiaingreso as gi')
                ->select('gi.Codigo', DB::raw("CONCAT(gi.Serie, '-', gi.Numero) as DocGuia"))
                ->where('gi.Vigente', 1)
                ->where('gi.CodigoSede', $sede)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('detalleguiaingreso as dgi')
                        ->join('producto as p', 'p.Codigo', '=', 'dgi.CodigoProducto')
                        ->leftJoin(DB::raw('(
                            SELECT 
                                CodigoProducto,
                                CodigoDetalleIngreso,
                                SUM(Cantidad) as Cantidad,
                                SUM(Costo) as Costo
                            FROM lote 
                            WHERE Vigente = 1
                            GROUP BY CodigoProducto, CodigoDetalleIngreso
                        ) AS LOTEREG'), function ($join) {
                            $join->on('LOTEREG.CodigoProducto', '=', 'dgi.CodigoProducto')
                                ->on('LOTEREG.CodigoDetalleIngreso', '=', 'dgi.Codigo');
                        })
                        ->whereRaw('dgi.CodigoGuiaRemision = gi.Codigo')
                        ->whereRaw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad, 0)) > 0');
                })
                ->get();

            // Log de éxito
            Log::info('Guías de Ingreso listadas correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'listarGuiasIngreso',
                'cantidad' => count($resultados),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($resultados, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Ocurrió un error al listar Guías de Ingreso', [
                'Controlador' => 'LoteController',
                'Metodo' => 'listarGuiasIngreso',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar Guías de Ingreso.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarDetalleGuia($codigo)
    {
        try {

            $resultados = DB::table('detalleguiaingreso as dgi')
                ->join('producto as p', 'p.Codigo', '=', 'dgi.CodigoProducto')
                ->leftJoin(DB::raw('(
                SELECT 
                    CodigoProducto,
                    CodigoDetalleIngreso,
                    SUM(Cantidad) as Cantidad,
                    SUM(Costo) as Costo
                FROM lote 
                WHERE Vigente = 1
                GROUP BY CodigoProducto, CodigoDetalleIngreso
                ) AS LOTEREG'), function ($join) {
                    $join->on('LOTEREG.CodigoProducto', '=', 'dgi.CodigoProducto')
                        ->on('LOTEREG.CodigoDetalleIngreso', '=', 'dgi.Codigo');
                })
                ->where('dgi.CodigoGuiaRemision', $codigo)
                ->whereRaw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad,0)) > 0')
                ->select('dgi.Codigo', 'p.Nombre')
                ->get();

            // Log de éxito
            Log::info('Detalle de Guía listados correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'listarDetalleGuia',
                'cantidad' => count($resultados),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Ocurrió un error al listar Detalle de Guía', [
                'Controlador' => 'LoteController',
                'Metodo' => 'listarDetalleGuia',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al listar Detalle de Guía.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function detallexGuia($codigo)
    {
        try {
            $resultados = DB::table('detalleguiaingreso as dgi')
                ->leftJoin(DB::raw('(
                SELECT 
                    CodigoProducto,
                    SUM(Cantidad) as Cantidad,
                    SUM(Costo) as Costo
                FROM lote 
                WHERE CodigoDetalleIngreso = ' . $codigo . '
                    AND Vigente = 1
                GROUP BY CodigoProducto
            ) AS LOTEREG'), function ($join) {
                    $join->on('LOTEREG.CodigoProducto', '=', 'dgi.CodigoProducto');
                })
                ->join('sedeproducto as sp', 'dgi.CodigoProducto', '=', 'sp.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->where('dgi.Codigo', $codigo)
                ->whereRaw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad,0)) > 0')
                ->select(
                    'dgi.Codigo as CodigoDetalleIngreso',
                    DB::raw('(dgi.Cantidad - COALESCE(LOTEREG.Cantidad,0)) as Cantidad'),
                    DB::raw('(dgi.Costo - COALESCE(LOTEREG.Costo,0)) as Costo'),
                    'dgi.CodigoProducto',
                    'tg.Porcentaje'
                )
                ->first();

            if (!$resultados) {
                Log::warning('No se encontraron resultados', [
                    'Controlador' => 'LoteController',
                    'Metodo' => 'detallexGuia',
                    'Codigo' => $codigo
                ]);

                return response()->json(['error' => 'No se encontraron resultados para el código proporcionado.'], 404);
            }

            // Log de éxito
            Log::info('Detalle de Guía consultado correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'detallexGuia',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($resultados, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Ocurrió un error al consultar Detalle de Guía', [
                'Controlador' => 'LoteController',
                'Metodo' => 'detallexGuia',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar Detalle de Guía.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarLote(Request $request)
    {
        $data = $request->all();
        $inversionLote = 0;

        //Consultar Sede
        $stockSede = 0;
        $costoSede = 0;
        $inversionSede = 0;

        //Movimiento Lote
        $nuevoStock = 0;
        $nuevoCosto = 0;
        $nuevaInversion = 0;

        $fechaActual = date('Y-m-d H:i:s');
        DB::beginTransaction();

        try {

            foreach ($data as $lote) {
                $inversionLote = 0;

                $producto = DB::table('sedeproducto')
                    ->where('CodigoProducto', $lote['CodigoProducto'])
                    ->where('CodigoSede', $lote['CodigoSede'])
                    ->first();

                $stockSede = $producto->Stock ?? 0;
                $costoSede = $producto->CostoCompraPromedio ?? 0;
                $inversionSede = $stockSede * $costoSede;

                $inversionLote = $lote['Costo'] * $lote['Cantidad'];

                $nuevoStock = $lote['Cantidad'] + $stockSede;
                $nuevaInversion = $inversionSede + $inversionLote;
                $nuevoCosto = $nuevaInversion / $nuevoStock;

                $lote['Stock'] = $lote['Cantidad'];

                $loteCreado = Lote::create($lote);

                $movimientoLote['CodigoDetalleIngreso'] = $lote['CodigoDetalleIngreso'];
                $movimientoLote['CodigoLote'] = $loteCreado->Codigo;
                $movimientoLote['Cantidad'] = $lote['Cantidad'];
                $movimientoLote['Stock'] = $nuevoStock;
                $movimientoLote['CostoPromedio'] = $nuevoCosto;
                $movimientoLote['Fecha'] = $fechaActual;
                $movimientoLote['TipoOperacion'] = 'I';
                MovimientoLote::create($movimientoLote);

                DB::table('sedeproducto')
                    ->where('CodigoProducto', $lote['CodigoProducto'])
                    ->where('CodigoSede', $lote['CodigoSede'])
                    ->update([
                        'CostoCompraPromedio' => $nuevoCosto,
                        'Stock' => $nuevoStock
                    ]);
            }

            DB::commit();
            Log::info('Lote registrado correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'registrarLote',
                'Codigo' => $loteCreado->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($lote, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ocurrió un error al registrar Lote', [
                'Controlador' => 'LoteController',
                'Metodo' => 'registrarLote',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al registrar Lote.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarLote($codigo)
    {
        try {
            $lote = DB::table('lote as l')
                ->join('detalleguiaingreso as dgi', 'l.CodigoDetalleIngreso', '=', 'dgi.Codigo')
                ->join('producto as p', 'dgi.CodigoProducto', '=', 'p.Codigo')
                ->join('guiaingreso as gi', 'dgi.CodigoGuiaRemision', '=', 'gi.Codigo')
                ->where('l.Codigo', $codigo)
                ->select(
                    'l.Codigo',
                    DB::raw("CONCAT(gi.Serie, ' - ', gi.Numero) as Documento"),
                    'p.Nombre as Producto',
                    'l.Cantidad',
                    'l.Costo',
                    'l.MontoIGV',
                    'l.FechaCaducidad as Fecha',
                    'l.Serie',
                    'l.Vigente'
                )
                ->first(); // O ->get() si esperas múltiples resultados
            if (!$lote) {
                // Log del error específico
                Log::warning('Lote no encontrado', [
                    'Controlador' => 'LoteController',
                    'Metodo' => 'consultarLote',
                    'Codigo' => $codigo
                ]);
                return response()->json(['error' => 'Lote no encontrado.'], 404);
            }
            // Log de éxito
            Log::info('Lote consultado correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'consultarLote',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($lote, 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Ocurrió un error al consultar Lote', [
                'Controlador' => 'LoteController',
                'Metodo' => 'consultarLote',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al consultar Lote.', 'bd' => $e->getMessage()], 500);
        }
    }

    public function actualizarLote(Request $request)
    {

        DB::beginTransaction();
        try {
            $loteEncontrado = Lote::find($request->Codigo);

            if (!$loteEncontrado) {
                // Log del error específico
                Log::warning('Lote no encontrado', [
                    'Controlador' => 'LoteController',
                    'Metodo' => 'actualizarLote',
                    'Codigo' => $request->Codigo
                ]);
                return response()->json(['error' => 'Lote no encontrado.'], 404);
            }

            if ($loteEncontrado->Vigente == 0) {
                // Log del error específico
                Log::warning('Intento de actualización de Lote inactivo', [
                    'Controlador' => 'LoteController',
                    'Metodo' => 'actualizarLote',
                    'Codigo' => $request->Codigo
                ]);
                return response()->json(['error' => 'No se puede actualizar un Lote en estado Inactivo.'], 404);
            }

            // if (($loteEncontrado->Cantidad != $loteEncontrado->Stock) && ($request->Vigente == 0)) {
            //     return response()->json(['error' => 'No se puede dar de baja un Lote con Stock diferente a la Cantidad.'], 404);
            // }

            if ($request->Vigente == 0) {
                //validar que no se ha generado egresos de productos de este lote
                if (($loteEncontrado->Cantidad != $loteEncontrado->Stock)) {
                    // Log del error específico
                    Log::warning('Intento de anulación de Lote con Stock diferente a la Cantidad', [
                        'Controlador' => 'LoteController',
                        'Metodo' => 'actualizarLote',
                        'Codigo' => $request->Codigo,
                        'Cantidad' => $loteEncontrado->Cantidad,
                        'Stock' => $loteEncontrado->Stock
                    ]);
                    return response()->json(['error' => 'No se puede anular un Lote con Stock diferente a la Cantidad.'], 404);
                }
                //Buscar si es el ultimo lote activo
                $ultimoLote = DB::table('lote')
                    ->select('Codigo', 'Serie', 'Cantidad', 'CodigoProducto')
                    ->where('Vigente', 1)
                    ->orderByDesc('Codigo')
                    ->limit(1)
                    ->first();
                //Verificar si el que ingresa es el ultimo lote activo
                if ($request->Codigo != $ultimoLote->Codigo) {
                    // Log del error específico
                    Log::warning('Intento de anulación de Lote no es el último activo', [
                        'Controlador' => 'LoteController',
                        'Metodo' => 'actualizarLote',
                        'Codigo' => $request->Codigo,
                        'UltimoLote' => $ultimoLote->Codigo
                    ]);
                    return response()->json([
                        'error' => 'No se puede anular este lote. Primero debe eliminar el lote de Serie: ' . $ultimoLote->Serie
                    ], 404);
                }
                //Actualizar el movimiento Lote (Vigente = 0)

                DB::table('movimientolote')
                    ->where('CodigoLote', $request->Codigo)
                    ->update([
                        'Vigente' => 0,
                    ]);

                //Actualizar el lote (Vigente = 0)

                $loteEncontrado->update(
                    [
                        'Vigente' => 0,
                    ]
                );

                //Buscar el ultimo movimiento de lote activo
                $ultimoMovimientoLoteActivo = DB::table('movimientolote as ml')
                    ->join('lote as l', 'ml.CodigoLote', '=', 'l.Codigo')
                    ->select('ml.CostoPromedio')
                    ->where('l.CodigoProducto', $ultimoLote->CodigoProducto)
                    ->where('ml.Vigente', 1)
                    ->orderByDesc('ml.Codigo')
                    ->limit(1)
                    ->first();


                // Actualizar el stock de la sede

                if ($ultimoMovimientoLoteActivo) {
                    DB::table('sedeproducto')
                        ->where('CodigoProducto', $ultimoLote->CodigoProducto)
                        ->update([
                            'CostoCompraPromedio' => $ultimoMovimientoLoteActivo->CostoPromedio,
                            'Stock' => DB::raw('Stock - ' . $ultimoLote->Cantidad)
                        ]);
                } else {


                    DB::table('sedeproducto')
                        ->where('CodigoProducto', $ultimoLote->CodigoProducto)
                        ->update([
                            'CostoCompraPromedio' => 0,
                            'Stock' => 0
                        ]);


                    // return response()->json([
                    //     'error' => 'No se encontró un movimiento de lote activo para actualizar el producto.'
                    // ], 404);
                }
            } else {
                $loteEncontrado->update(
                    [
                        'FechaCaducidad' => $request->Fecha,
                        'Serie' => $request->Serie,
                    ]
                );
            }

            DB::commit();
            Log::info('Lote actualizado correctamente', [
                'Controlador' => 'LoteController',
                'Metodo' => 'actualizarLote',
                'Codigo' => $loteEncontrado->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Se actualizó el lote correctamente.', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error general
            Log::error('Ocurrió un error al actualizar Lote', [
                'Controlador' => 'LoteController',
                'Metodo' => 'actualizarLote',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al actualizar Lote.', 'bd' => $e->getMessage()], 500);
        }
    }
}
