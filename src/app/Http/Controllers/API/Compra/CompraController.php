<?php

namespace App\Http\Controllers\API\Compra;

use App\Http\Controllers\Controller;
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

            return response()->json([
                'compra' => $compra,
                'detalleCompra' => $detaleCompra,
                'tipoMoneda' => $tipoMoneda,
                'porcentaje' => $porcentaje,
                'razonSocial' => $razonSocial
            ], 200);
        } catch (\Exception $e) {
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
                ->get();

            return response()->json($proveedores, 200);
        } catch (\Exception $e) {
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
                ->where('p.Nombre', 'LIKE', "{$nombre}%") // Filtro por Nombre
                ->get();


            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar los productos', $e], 500);
        }
    }

    public function listarCompras(Request $request)
    {
        $filtro = $request->all();

        try {

            $compra = DB::table('compra as c')
                ->join('proveedor as p', 'p.Codigo', '=', 'c.CodigoProveedor')
                ->join('detallecompra as dc', 'dc.CodigoCompra', '=', 'c.Codigo')
                ->select(
                    'c.Codigo',
                    'c.Serie',
                    'c.Numero',
                    'c.Fecha',
                    'p.RazonSocial',
                    'p.Codigo as CodigoProveedor',
                    'c.Vigente'
                )
                ->where('c.CodigoSede', $filtro['sede'])
                ->where(function ($query) use ($filtro) {
                    if ($filtro['tipo'] == 1) {
                        $query->whereNotNull('dc.CodigoProducto'); // Si tipo = 1, solo productos NO NULL
                    } elseif ($filtro['tipo'] == 0) {
                        $query->whereNull('dc.CodigoProducto'); // Si tipo = 0, solo productos NULL
                    }
                })
                ->orderBy('c.Codigo', 'desc')
                ->get();


            return response()->json($compra, 200);
        } catch (\Exception $e) {
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
            return response()->json($result, 200);
        } catch (\Exception $e) {
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

        try {
            DB::beginTransaction();

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
            return response()->json(['message' => 'Compra registrada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la compra', 'error' => $e], 500);
        }
    }

    public function actualizarCompra(Request $request)
    {
        $compra = $request->input('compra');
        DB::beginTransaction();
        try {

            // $compra = Compra::find($compra['Codigo']);
            // $compra->update($request->input('compra'));

            // Que no tenga pago activos
            $existePagoActivo = DB::table('cuota as cu')
                ->join('pagoproveedor as pp', 'cu.Codigo', '=', 'pp.CodigoCuota')
                ->join('egreso as e', 'pp.Codigo', '=', 'e.Codigo')
                ->where('cu.CodigoCompra', $compra['Codigo'])
                ->where('e.Vigente', 1)
                ->exists();

            if ($existePagoActivo) {
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
                return response()->json(['error' => 'No se puede actualizar la compra porque tiene una guía de ingreso activa.'], 400);
            }

            // Verificar si la compra existe

            $compraEncontrada = Compra::find($compra['Codigo']);
            if (!$compraEncontrada) {
                return response()->json(['error' => 'Compra no encontrada'], 404);
            }

            // Actualizar la compra
            if ($compraEncontrada['Vigente'] == 1) {
                $compraEncontrada->update(['Vigente' => $compra['Vigente']]);
                DB::table('cuota')
                    ->where('CodigoCompra', $compra['Codigo'])
                    ->update(['Vigente' => 0]);
            } else {
                return response()->json([
                    'error' => __('mensajes.error_act_compra')
                ], 400);
            }

            DB::commit();
            return response()->json(['message' => 'Compra actualizada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar la compra', 'bd' => $e->getMessage()], 500);
        }
    }
}
