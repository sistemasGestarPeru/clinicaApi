<?php

namespace App\Http\Controllers\API\Compra;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaSalida\DetalleGuiaSalida;
use App\Models\Almacen\GuiaSalida\GuiaSalida;
use App\Models\Almacen\Lote\MovimientoLote;
use App\Models\Recaudacion\Compra;
use App\Models\Recaudacion\Cuota;
use App\Models\Recaudacion\DetalleCompra;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\PagoProveedor;
use App\Models\Recaudacion\Proveedor;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompraController extends Controller
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

    public function consultarCompra($codigo)
    {
        try {
            $compra = Compra::find($codigo);
            $detaleCompra = DB::table('detallecompra')
                ->where('CodigoCompra', $codigo)
                ->get();

            $tipoMoneda = DB::table('cuota')
                ->where('CodigoCompra', $codigo)
                ->limit(1)
                ->value('TipoMoneda');

            $porcentaje = DB::table('tipogravado')
                ->where('Tipo', 'G')
                ->value('Porcentaje');

            $razonSocial = DB::table('proveedor')
                ->where('Codigo', $compra->CodigoProveedor)
                ->value('RazonSocial');

            if ($compra == null) {
                return response()->json(['message' => 'No se encontró la venta'], 404);
            }

            // Log de éxito
            Log::info('Compra consultada correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'consultarCompra',
                'codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'compra' => $compra,
                'detalleCompra' => $detaleCompra,
                'tipoMoneda' => $tipoMoneda,
                'porcentaje' => $porcentaje,
                'razonSocial' => $razonSocial
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar la compra', [
                'Controlador' => 'CompraController',
                'Metodo' => 'consultarCompra',
                'codigo' => $codigo,
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Error al consultar la venta'], 500);
        }
    }

    public function listarProveedor(Request $request)
    {

        $nombre = $request->input('nombre');
        $ruc = $request->input('ruc');

        try {
            $proveedores = DB::table('proveedor')
                ->where('Vigente', 1)
                // ->where('RazonSocial', 'like', '%'.$nombre.'%')
                // ->where('RUC', 'like', '%'.$ruc.'%')
                ->select('Codigo', 'RazonSocial', 'RUC')
                ->orderBy('RazonSocial', 'asc')
                ->get();

            // Log de éxito
            Log::info('Proveedores listados correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarProveedor',
                'cantidad' => count($proveedores),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($proveedores, 200);
        } catch (\Exception $e) {

            Log::error('Error al listar los proveedores', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarProveedor',
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Error al listar los proveedores'], 500);
        }
    }

    public function listarProducto(Request $request)
    {
        $sede = $request->input('sede');
        $nombre = $request->input('nombre');
        try {
            $productos = DB::table('sedeproducto as sp')
                ->select(
                    'p.Codigo',
                    'p.Nombre',
                    'tg.Tipo as TipoGravado',
                    'tg.Porcentaje'
                )
                ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->where('sp.CodigoSede', $sede) // Filtro por CódigoSede
                ->where('p.Tipo', 'B') // Filtro por Tipo = 'B'
                ->where('sp.Vigente', 1) // Filtro por Vigente en sedeproducto
                ->where('p.Vigente', 1) // Filtro por Vigente en producto
                ->where('tg.Vigente', 1) // Filtro por Vigente en tipogravado
                ->where('p.Nombre', 'LIKE', "%{$nombre}%") // Filtro por Nombre
                ->orderBy('p.Nombre', 'asc') // Ordenar por Nombre
                ->get();

            // Log de éxito
            Log::info('Productos listados correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarProducto',
                'cantidad' => count($productos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar los productos', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarProducto',
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Error al listar los productos', $e], 500);
        }
    }

    public function listarCompras(Request $request)
    {
        $filtro = $request->all();
        $mes = $filtro['mes'];
        $anio = $filtro['anio'];
        $estadoPago = $filtro['estadoPago'];
        $serie = $filtro['serie'];
        $numero = $filtro['numero'];
        try {

            $compra = DB::table('compra as c')
                ->join('proveedor as p', 'p.Codigo', '=', 'c.CodigoProveedor')

                // LEFT JOIN con subconsulta de cuotas (MontoPagar)
                ->leftJoinSub(
                    DB::table('cuota')
                        ->select('CodigoCompra', DB::raw('SUM(Monto) as MontoPagar'), 'TipoMoneda')
                        ->groupBy('CodigoCompra', 'TipoMoneda'),
                    'cuotas',
                    'cuotas.CodigoCompra',
                    '=',
                    'c.Codigo'
                )

                // LEFT JOIN con subconsulta de pagos (MontoPagado)
                ->leftJoinSub(
                    DB::table('cuota as cu')
                        ->leftJoin('pagoproveedor as pp', 'cu.Codigo', '=', 'pp.CodigoCuota')
                        ->leftJoin('egreso as e', 'e.Codigo', '=', 'pp.Codigo')
                        ->leftJoin('tipomoneda as m', 'cu.TipoMoneda', '=', 'm.Codigo')
                        ->select(
                            'cu.CodigoCompra',
                            DB::raw("SUM(CASE 
                                    WHEN m.Siglas = 'PEN' THEN e.Monto 
                                    ELSE pp.MontoMonedaExtranjera 
                                END) AS MontoPagado")
                        )
                        ->groupBy('cu.CodigoCompra'),
                    'pagos',
                    'pagos.CodigoCompra',
                    '=',
                    'c.Codigo'
                )

                // LEFT JOIN con subconsulta de vencimiento (FechaVencimiento)
                ->leftJoinSub(
                    DB::table('cuota as cu')
                        ->leftJoin('pagoproveedor as pp', 'cu.Codigo', '=', 'pp.CodigoCuota')
                        ->leftJoin('egreso as e', 'e.Codigo', '=', 'pp.Codigo')
                        ->select('cu.CodigoCompra', DB::raw('MIN(cu.Fecha) as FechaVencimiento'))
                        ->whereRaw('(e.Codigo IS NULL OR e.Monto < cu.Monto)')
                        ->groupBy('cu.CodigoCompra'),
                    'vencimiento',
                    'vencimiento.CodigoCompra',
                    '=',
                    'c.Codigo'
                )

                ->leftJoin('tipomoneda as m', 'm.Codigo', '=', 'cuotas.TipoMoneda')
                ->leftJoin('tipodocumentoventa as tdv', 'c.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')

                // Campos que deseas recuperar
                ->select(
                    'c.Codigo',
                    'c.Serie',
                    'c.Numero',
                    'tdv.Siglas as TipoDocumento',
                    'c.Fecha',
                    'p.RazonSocial',
                    'p.Codigo as CodigoProveedor',
                    'c.Vigente',
                    'cuotas.TipoMoneda',
                    DB::raw('IFNULL(cuotas.MontoPagar, 0) as MontoPagar'),
                    DB::raw('IFNULL(pagos.MontoPagado, 0) as MontoPagado'),
                    DB::raw('IFNULL(vencimiento.FechaVencimiento, NULL) as FechaVencimiento'),
                    DB::raw('IFNULL(m.Siglas, "N/A") as TipoMoneda'),
                    'c.CodigoDocumentoReferencia'
                )

                // Filtro por sede
                ->where('c.CodigoSede', $filtro['sede'])

                // Filtro por fecha o rango
                ->when($anio, function ($query) use ($mes, $anio) {
                    if (empty($mes)) {
                        $query->whereYear('c.Fecha', $anio); //buscar por año
                    } else {
                        $query->whereYear('c.Fecha', $anio)->whereMonth('c.Fecha', $mes); //mes y año
                    }
                })

                //Serie 
                ->when($serie, function ($query) use ($serie) {
                    $query->where('c.Serie', 'like', "{$serie}%");
                })

                //Numero
                ->when($numero, function ($query) use ($numero) {
                    $query->where('c.Numero', 'like', "{$numero}%");
                })

                // Filtro por estado de pago
                ->when($estadoPago == 1, function ($query) {
                    $query->whereRaw('IFNULL(cuotas.MontoPagar, 0) = IFNULL(pagos.MontoPagado, 0)');
                })
                ->when($estadoPago == 2, function ($query) {
                    $query->whereRaw('IFNULL(cuotas.MontoPagar, 0) != IFNULL(pagos.MontoPagado, 0)');
                })

                // Filtro por tipo
                ->where(function ($query) use ($filtro) {
                    if ($filtro['tipo'] == 1) {
                        $query->whereExists(function ($q) {
                            $q->select(DB::raw(1))
                                ->from('detallecompra as dc')
                                ->whereColumn('dc.CodigoCompra', 'c.Codigo')
                                ->whereNotNull('dc.CodigoProducto');
                        });
                    } elseif ($filtro['tipo'] == 0) {
                        $query->whereExists(function ($q) {
                            $q->select(DB::raw(1))
                                ->from('detallecompra as dc')
                                ->whereColumn('dc.CodigoCompra', 'c.Codigo')
                                ->whereNull('dc.CodigoProducto');
                        });
                    }
                })

                ->orderByDesc('c.Codigo')
                ->get();

            // Log de éxito
            Log::info('Compras listadas correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarCompras',
                'cantidad' => count($compra),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($compra, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar las compras', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarCompras',
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al listar las compras', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarPagosAdelantados(Request $request)
    {
        $Proveedor = $request->input('proveedor');
        $Moneda = $request->input('moneda');
        try {
            $result = DB::table('pagoproveedor as pp')
                ->join('egreso as e', 'e.Codigo', '=', 'pp.Codigo')
                ->join('tipomoneda as tp', 'tp.Codigo', '=', 'pp.tipomoneda')
                ->select(
                    'e.Codigo as CodigoE',
                    'tp.Siglas as TipoMoneda',
                    'tp.Codigo as CodigoMoneda',
                    DB::raw("
                    CASE 
                        WHEN pp.TipoMoneda = 1 THEN e.Monto
                        ELSE pp.MontoMonedaExtranjera
                    END AS Monto
                ")
                )
                ->where('pp.CodigoProveedor', $Proveedor)
                ->where('pp.TipoMoneda', $Moneda)
                ->whereNull('pp.CodigoCuota')
                ->get();

            // Log de éxito
            Log::info('Pagos adelantados listados correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarPagosAdelantados',
                'cantidad' => count($result),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar los pagos adelantados', [
                'Controlador' => 'CompraController',
                'Metodo' => 'listarPagosAdelantados',
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Error al listar los pagos adelantados'], 500);
        }
    }


    public function registrarCompra(Request $request)
    {

        $compra = $request->input('compra');
        $detalleCompra = $request->input('detalleCompra');
        $cuotas = $request->input('cuota');
        $egreso = $request->input('egreso');
        $proveedor = $request->input('proveedor');
        $proveedor['CodigoProveedor'] = $compra['CodigoProveedor'];

        $MontoTotal = 0;

        DB::beginTransaction();

        try {

            $compraData = Compra::create($compra);
            $idCompra = $compraData->Codigo;

            foreach ($detalleCompra as $detalle) {
                $detalle['CodigoCompra'] = $idCompra;
                $MontoTotal += $detalle['MontoTotal'];
                DetalleCompra::create($detalle);
            }

            if ($compra['FormaPago'] == 'C') {

                $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
                $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
                $fechaCompraVal = Carbon::parse($compra['Fecha'])->toDateString(); // Convertir el string a Carbon

                if ($fechaCajaVal < $fechaCompraVal) {
                    return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
                }

                if (isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0) {
                    $egreso['CodigoCuentaOrigen'] = null;
                }

                if (isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0) {
                    $egreso['CodigoBilleteraDigital'] = null;
                }

                if ($egreso['CodigoSUNAT'] == '008') {
                    $egreso['CodigoCuentaOrigen'] = null;
                    $egreso['CodigoBilleteraDigital'] = null;
                    $egreso['Lote'] = null;
                    $egreso['Referencia'] = null;
                    $egreso['NumeroOperacion'] = null;

                    $fechaPagoVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

                    if ($fechaCajaVal < $fechaPagoVal) {
                        return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
                    }

                    $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

                    if ($egreso['Monto'] > $total) {
                        return response()->json(['error' => 'No hay suficiente Efectivo en caja', 'Disponible' => $total], 500);
                    }
                } else if ($egreso['CodigoSUNAT'] == '003') {
                    $egreso['Lote'] = null;
                    $egreso['Referencia'] = null;
                } else if ($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006') {
                    $egreso['CodigoCuentaBancaria'] = null;
                    $egreso['CodigoBilleteraDigital'] = null;
                }

                $nuevoEgreso = Egreso::create($egreso);
                $idEgreso = $nuevoEgreso->Codigo;
            }

            foreach ($cuotas as $cuota) {

                $cuota['CodigoCompra'] = $idCompra;
                $cutaData = Cuota::create($cuota);
                $idCuota = $cutaData->Codigo;

                if (!empty($cuota['CodigoE'])) {
                    DB::table('pagoproveedor')
                        ->where('Codigo', $cuota['CodigoE'])
                        ->update(['CodigoCuota' => $idCuota]);
                } else {
                    if ($compra['FormaPago'] == 'C') {
                        $proveedor['Codigo'] = $idEgreso;
                        $proveedor['CodigoCuota'] = $idCuota;
                        PagoProveedor::create($proveedor);
                    }
                }
            }

            DB::commit();
            // Log de éxito
            Log::info('Compra registrada correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'registrarCompra',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Compra registrada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar la compra', [
                'Controlador' => 'CompraController',
                'Metodo' => 'registrarCompra',
                'error' => $e->getMessage(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Error al registrar la compra', 'error' => $e], 500);
        }
    }

    public function actualizarCompra(Request $request)
    {
        $compra = $request->input('compra');
        DB::beginTransaction();
        try {

            // Verificar si la compra existe
            $compraEncontrada = Compra::find($compra['Codigo']);
            if (!$compraEncontrada) {
                // Log del error específico
                Log::warning('Compra no encontrada para actualizar', [
                    'Controlador' => 'CompraController',
                    'Metodo' => 'actualizarCompra',
                    'CodigoCompra' => $compra['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['error' => 'Compra no encontrada'], 404);
            }

            // Actualizar la compra
            if ($compraEncontrada['Vigente'] == 0) {
                // Log del error específico
                Log::warning('Compra no puede ser actualizada porque ya no está vigente', [
                    'Controlador' => 'CompraController',
                    'Metodo' => 'actualizarCompra',
                    'CodigoCompra' => $compra['Codigo'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json([
                    'error' => __('mensajes.error_act_compra')
                ], 400);
            }

            if ($compra['Vigente'] == 0) {
                // Que no tenga pago activos
                $existePagoActivo = DB::table('cuota as cu')
                    ->join('pagoproveedor as pp', 'cu.Codigo', '=', 'pp.CodigoCuota')
                    ->join('egreso as e', 'pp.Codigo', '=', 'e.Codigo')
                    ->where('cu.CodigoCompra', $compra['Codigo'])
                    ->where('e.Vigente', 1)
                    ->exists();

                if ($existePagoActivo) {
                    // Log del error específico
                    Log::warning('No se puede actualizar la compra porque tiene pagos activos', [
                        'Controlador' => 'CompraController',
                        'Metodo' => 'actualizarCompra',
                        'CodigoCompra' => $compra['Codigo'],
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['error' => 'No se puede actualizar la compra porque tiene pagos activos.'], 400);
                }

                // Verificar si la compra tiene guia ingreso

                $guiaActiva = DB::table('compra as c')
                    ->join('guiaingreso as g', 'c.Codigo', '=', 'g.CodigoCompra')
                    ->where('g.Vigente', 1)
                    ->where('c.Codigo', $compra['Codigo'])
                    ->where('c.Vigente', 1)
                    ->exists();

                if ($guiaActiva) {
                    // Log del error específico
                    Log::warning(
                        'No se puede actualizar la compra porque tiene una guía de ingreso activa',
                        [
                            'Controlador' => 'CompraController',
                            'Metodo' => 'actualizarCompra',
                            'CodigoCompra' => $compra['Codigo'],
                            'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                        ]
                    );
                    return response()->json(['error' => 'No se puede actualizar la compra porque tiene una guía de ingreso activa.'], 400);
                }

                $compraEncontrada->update(['Vigente' => $compra['Vigente']]);
                DB::table('cuota')
                    ->where('CodigoCompra', $compra['Codigo'])
                    ->update(['Vigente' => 0]);
            } else {
                $compraEncontrada->update(
                    [
                        'Fecha' => $compra['Fecha'],
                        'CodigoTipoDocumentoVenta' => $compra['CodigoTipoDocumentoVenta'],
                        'Serie' => $compra['Serie'],
                        'Numero' => $compra['Numero']
                    ]
                );
            }


            DB::commit();
            // Log de éxito
            Log::info('Compra actualizada correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'actualizarCompra',
                'CodigoCompra' => $compra['Codigo'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Compra actualizada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error general
            Log::error('Error inesperado al actualizar la compra', [
                'Controlador' => 'CompraController',
                'Metodo' => 'actualizarCompra',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al actualizar la compra', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarDetalleComprasNC($codigo)
    { //para nota de credito

        try {

            // Primer query
            $compra = DB::table('compra as c')
                ->join('proveedor as p', 'c.CodigoProveedor', '=', 'p.Codigo')
                ->join('tipodocumentoventa as tdv', 'c.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')
                ->select(
                    'c.Codigo',
                    'c.Serie',
                    'c.Numero',
                    'c.Fecha',
                    'p.Codigo as CodigoProveedor',
                    DB::raw("CONCAT(p.RazonSocial,' ', p.RUC) as Proveedor"),
                    'tdv.Nombre as Documento',
                    'c.CodigoSede'
                )
                ->where('c.Codigo', $codigo)
                ->first();

            // Segundo query
            $detalleCompra = DB::table('producto as p')
                ->joinSub(function ($q) use ($codigo) {
                    $q->select(
                        'DDNC.CodigoProducto',
                        'DDNC.Descripcion',
                        'DDNC.Codigo',
                        DB::raw('SUM(DDNC.Cantidad) + COALESCE(MAX(NOTAC.CantidadBoleteada), 0) AS Cantidad'),
                        DB::raw('SUM(DDNC.MontoTotal) + COALESCE(MAX(NOTAC.MontoBoleteado), 0) AS Monto')
                    )
                        ->from('detallecompra as DDNC')
                        ->leftJoin(DB::raw("(
                            SELECT 
                                DNC.CodigoProducto, 
                                SUM(DNC.Cantidad) AS CantidadBoleteada, 
                                SUM(DNC.MontoTotal) AS MontoBoleteado
                            FROM compra AS NC
                            INNER JOIN detallecompra AS DNC 
                                ON NC.Codigo = DNC.CodigoCompra
                            WHERE NC.CodigoDocumentoReferencia = {$codigo}
                            AND NC.Vigente = 1
                            GROUP BY DNC.CodigoProducto
                        ) AS NOTAC"), 'NOTAC.CodigoProducto', '=', 'DDNC.CodigoProducto')
                        ->where('DDNC.CodigoCompra', $codigo)
                        ->groupBy('DDNC.CodigoProducto', 'DDNC.Descripcion', 'DDNC.Codigo');
                }, 'S', function ($join) {
                    $join->on('p.Codigo', '=', 'S.CodigoProducto');
                })
                ->join('sedeproducto as SP', 'SP.CodigoProducto', '=', 'p.Codigo')
                ->join('tipogravado as TG', 'TG.Codigo', '=', 'SP.CodigoTipoGravado')
                ->select(
                    'S.CodigoProducto',
                    'S.Descripcion',
                    'TG.Tipo as TipoGravado',
                    'TG.Porcentaje',
                    'p.Tipo',
                    DB::raw("CASE WHEN p.Tipo = 'B' THEN S.Cantidad ELSE 1 END AS Cantidad"),
                    DB::raw('S.Monto AS MontoTotal')
                )
                ->where('S.Monto', '>', 0)
                ->where('SP.CodigoSede', $compra->CodigoSede)
                ->orderBy('S.Descripcion')
                ->get();



            // Log de éxito
            Log::info('Detalles de compra consultados correctamente', [
                'Controlador' => 'CompraController',
                'Metodo' => 'consultaDetallesCompra',
                'CodigoCompra' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'compra' => $compra,
                'detalleCompra' => $detalleCompra
            ]);
        } catch (\Exception $e) {
            Log::error('Error al consultar los detalles de la compra', [
                'Controlador' => 'CompraController',
                'Metodo' => 'consultaDetallesCompra',
                'CodigoCompra' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al consultar los detalles de la compra', 'bd' => $e->getMessage()], 500);
        }
    }

    public function registrarCompraNC(Request $request)
    {

        $compra = $request->input('compra');
        $detalleCompra = $request->input('detalleCompra');
        $egreso = $request->input('egreso');
        $proveedor = $request->input('proveedor');
        $proveedor['CodigoProveedor'] = $compra['CodigoProveedor'];
        $MontoTotal = 0;

        $fechaActual = date('Y-m-d H:i:s');

        DB::beginTransaction();

        try {

            $compraData = Compra::create($compra);
            $idCompra = $compraData->Codigo;

            // 1. Buscar si la compra tiene guia ingreso
            $guia = DB::table('guiaingreso') //agregue vigente = 1
                ->where('CodigoCompra', $compra['CodigoDocumentoReferencia'])
                ->where('Vigente', 1)
                ->select('Codigo')
                ->get();

            foreach ($detalleCompra as $detalle) {

                $detalle['CodigoCompra'] = $idCompra;
                $MontoTotal += $detalle['MontoTotal'];

                $detalle['MontoTotal'] = $detalle['MontoTotal'] * -1;
                $detalle['MontoIGV'] = $detalle['MontoIGV'] * -1;
                $detalle['Cantidad'] = $detalle['Cantidad'] * -1;

                DetalleCompra::create($detalle);

                $cantidad = abs($detalle['Cantidad']);
                // 2. Validar si existe la guia
                if ($guia->count() > 0) {

                    $lotes = DB::table('guiaingreso as gi')
                        ->join('detalleguiaingreso as dgi', 'gi.Codigo', '=', 'dgi.CodigoGuiaRemision')
                        ->join('lote as l', 'dgi.Codigo', '=', 'l.CodigoDetalleIngreso')
                        ->select('l.Codigo', 'l.Stock', 'l.Costo')
                        ->where('gi.CodigoCompra', $compra['CodigoDocumentoReferencia'])
                        ->where('gi.Vigente', 1)
                        ->where('dgi.CodigoProducto', $detalle['CodigoProducto'])
                        ->orderBy('FechaCaducidad', 'asc')
                        ->get();

                    // 3. Validar si tiene lotes
                    if ($lotes->count() > 0) {

                        // 4. Generar Guia Salida 
                        $guiaSalidaData = [
                            'TipoDocumento'    => 'G',
                            'Serie'            => $compra['Serie'],
                            'Numero'           => $compra['Numero'],
                            'Fecha'            => $fechaActual,
                            'Motivo'           => 'N',
                            'CodigoSede'       => $compra['CodigoSede'],
                            'CodigoTrabajador' => $compra['CodigoTrabajador'],
                        ];

                        $guiaSalidaCreada = GuiaSalida::create($guiaSalidaData);

                        $cantidadPendiente = abs($detalle['Cantidad']); // lo que debo devolver

                        foreach ($lotes as $lote) {
                            if ($cantidadPendiente <= 0) break;

                            // Lo que saco de este lote
                            $cantidadSalida = min($cantidadPendiente, $lote->Stock);

                            // Datos de la sede
                            $datosSede = DB::table('sedeproducto')
                                ->where('CodigoProducto', $detalle['CodigoProducto'])
                                ->where('CodigoSede', $compra['CodigoSede'])
                                ->where('Vigente', 1)
                                ->select('CostoCompraPromedio', 'Stock')
                                ->first();

                            // Crear detalle de salida
                            $detalleGuiaSalidaData = [
                                'Cantidad'         => $cantidadSalida,
                                'Costo'            => $lote->Costo,
                                'CodigoGuiaSalida' => $guiaSalidaCreada->Codigo,
                                'CodigoProducto'   => $detalle['CodigoProducto'],
                            ];
                            $detalleGuiaSalidaCreada = DetalleGuiaSalida::create($detalleGuiaSalidaData);

                            // Movimiento de lote
                            $movimientoLoteData = [
                                'CodigoDetalleSalida' => $detalleGuiaSalidaCreada->Codigo,
                                'CodigoLote'          => $lote->Codigo,
                                'TipoOperacion'       => 'N',
                                'Fecha'               => $fechaActual,
                                'Stock'               => $datosSede->Stock - $cantidadSalida,
                                'Cantidad'            => $cantidadSalida,
                                'CostoPromedio'       => $datosSede->CostoCompraPromedio,
                            ];
                            MovimientoLote::create($movimientoLoteData);

                            // Descontar en sede
                            DB::table('sedeproducto')
                                ->where('CodigoProducto', $detalle['CodigoProducto'])
                                ->where('CodigoSede', $compra['CodigoSede'])
                                ->decrement('Stock', $cantidadSalida);

                            // Descontar en lote
                            DB::table('lote')
                                ->where('Codigo', $lote->Codigo)
                                ->decrement('Stock', $cantidadSalida);

                            // Actualizar cantidad pendiente
                            $cantidadPendiente -= $cantidadSalida;
                        }
                    }
                }
            }

            Cuota::create(
                [
                    'CodigoCompra' => $idCompra,
                    'Fecha' => $compra['Fecha'],
                    'Monto' => $MontoTotal * -1,
                    'TipoMoneda' => 1,
                    'Vigente' => 1
                ]
            );

            DB::commit();

            Log::info('Nota de Credito Compra', [
                'Controlador' => 'CompraController',
                'Metodo' => 'registrarCompraNC',
                'idCompra' => $idCompra,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['message' => 'Nota de Credito Compra registrada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar la Nota de Credito Compra', [
                'Controlador' => 'CompraController',
                'Metodo' => 'registrarCompraNC',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al registrar la Nota de Credito Compra', 'bd' => $e->getMessage()], 500);
        }
    }
}
