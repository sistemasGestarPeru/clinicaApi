<?php

namespace App\Http\Controllers\API\Almacen\GuiaIngreso;

use App\Http\Controllers\Controller;
use App\Models\Almacen\GuiaIngreso\DetalleGuiaIngreso;
use App\Models\Almacen\GuiaIngreso\GuiaIngreso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function listarGuiaIngreso(Request $request)
    {
        $filtros = $request->all();
        try {
            $guiaIngreso = DB::table('guiaingreso')
                ->select(
                    'Codigo',
                    DB::raw("
                    CASE 
                        WHEN TipoDocumento = 'G' THEN 'Guia de remisión'
                        WHEN TipoDocumento = 'N' THEN 'Nota de salida'
                        WHEN TipoDocumento = 'T' THEN 'Transformación'
                        WHEN TipoDocumento = 'C' THEN 'Documento compra'
                        ELSE 'Desconocido'
                    END AS TipoDocumento
                "),
                    DB::raw("CONCAT(Serie, ' - ', Numero) AS Documento"),
                    'Fecha',
                    'Vigente'
                )
                ->where('CodigoSede', $filtros['CodigoSede'])
                ->where('TipoDocumento', '!=', 'T')
                ->get();

            // Log de éxito
            Log::info('Usuarios listados correctamente', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'listarGuiaIngreso',
                'cantidad' => count($guiaIngreso),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($guiaIngreso, 200);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Ocurrió un error al listar las guias de ingreso', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'listarGuiaIngreso',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar las guias de ingreso', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarComprasActivas($sede)
    {

        try {
            $entregado = DB::table('guiaingreso as gi')
                ->join('detalleguiaingreso as dgi', 'dgi.CodigoGuiaRemision', '=', 'gi.Codigo')
                ->select(
                    'gi.CodigoCompra',
                    'dgi.CodigoProducto',
                    DB::raw('SUM(dgi.Cantidad) as Cantidad')
                )
                ->where('gi.Vigente', 1)
                ->groupBy('gi.CodigoCompra', 'dgi.CodigoProducto');

            $compras = DB::table('compra as c')
                ->select([
                    'c.Codigo',
                    'c.Serie',
                    DB::raw("LPAD(c.Numero, 6, '0') as Numero"),
                    'c.Fecha',
                ])
                ->where('c.CodigoSede', $sede)
                ->where('c.Vigente', 1)
                ->whereExists(function ($query) use ($entregado) {
                    $query->select(DB::raw(1))
                        ->from('detallecompra as dc')
                        ->join('producto as p', 'p.Codigo', '=', 'dc.CodigoProducto')
                        ->leftJoinSub($entregado, 'Entregado', function ($join) {
                            $join->on('Entregado.CodigoProducto', '=', 'dc.CodigoProducto')
                                ->on('Entregado.CodigoCompra', '=', 'dc.CodigoCompra');
                        })
                        ->whereColumn('dc.CodigoCompra', 'c.Codigo')  // Ahora sí es válido en este nivel
                        ->whereRaw('(dc.Cantidad - COALESCE(Entregado.Cantidad, 0)) > 0')
                        ->where('p.Tipo', 'B');
                })
                ->get();


            Log::info('Listado de Compras', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'listarComprasActivas',
                'cantidad' => count($compras),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json($compras, 200);
        } catch (\Exception $e) {

            Log::error('Ocurrió un error al listar las Compras', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'listarComprasActivas',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar las Compras', 'bd' => $e->getMessage()], 500);
        }
    }

    public function listarDetalleCompra($compra)
    {
        try {
            $detalleCompra = DB::table('detallecompra as dc')
                ->leftJoinSub(
                    DB::table('guiaingreso as gi')
                        ->join('detalleguiaingreso as dgi', 'dgi.CodigoGuiaRemision', '=', 'gi.Codigo')
                        ->where('gi.CodigoCompra', $compra)
                        ->where('gi.Vigente', 1)
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
                ->whereRaw('(dc.Cantidad - COALESCE(Entregado.Cantidad, 0)) > 0')
                ->select([
                    DB::raw('dc.Cantidad - COALESCE(Entregado.Cantidad, 0) as Cantidad'),
                    'dc.CodigoProducto',
                    'dc.Descripcion',
                    DB::raw('((dc.MontoTotal - dc.MontoIGV))/(dc.Cantidad - COALESCE(Entregado.Cantidad, 0)) AS Costo'),
                ])
                ->get();

            // Log de éxito
            Log::info('Detalle compra', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'listarDetalleCompra',
                'cantidad' => count($detalleCompra),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json($detalleCompra, 200);
        } catch (\Exception $e) {

            // Log del error
            Log::error('Ocurrió un error al listar los detalles de la compra', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'listarDetalleCompra',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json(['error' => 'Ocurrió un error al listar los detalles de la compra', 'bd' => $e->getMessage()], 500);
        }
    }


    public function registrarGuiaIngreso(Request $request)
    {
        $guiaData = $request->input('guiaIngreso');
        $detalleGuia = $request->input('detalleGuiaIngreso');
        DB::beginTransaction();

        try {

            $guiaIngreso = GuiaIngreso::create($guiaData);

            foreach ($detalleGuia as $detalle) {
                $detalle['CodigoGuiaRemision'] = $guiaIngreso->Codigo;
                DetalleGuiaIngreso::create($detalle);
            }


            DB::commit();
            // Log de éxito
            Log::info('Guia de Ingreso registrada correctamente', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'registrarGuiaIngreso',
                'Codigo' => $guiaIngreso->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['message' => 'Guia de Ingreso registrada correctamente', 'Codigo' => $guiaIngreso->Codigo], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error
            Log::error('Ocurrió un error al registrar la Guia de Ingreso', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'registrarGuiaIngreso',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al registar la Guia de Ingreso', 'bd' => $e->getMessage()], 500);
        }
    }

    public function consultarGuia($codigo)
    {
        try {

            $guiaIngreso = DB::table('guiaingreso')
                ->select('Codigo', 'TipoDocumento', 'Serie', 'Numero', 'Fecha', 'Vigente')
                ->where('Codigo', $codigo)
                ->first();

            if (!$guiaIngreso) {

                Log::warning('La guía de ingreso no existe.', [
                    'Controlador' => 'GuiaIngresoController',
                    'Metodo' => 'consultarGuia',
                    'Codigo' => $codigo
                ]);

                return response()->json(['error' => 'La guía de ingreso no existe.'], 404);
            }

            $detalleGuia = DB::table('detalleguiaingreso as dgi')
                ->join('producto as p', 'dgi.CodigoProducto', '=', 'p.Codigo')
                ->select('p.Nombre as Descripcion', 'dgi.Cantidad', 'dgi.Costo')
                ->where('dgi.CodigoGuiaRemision', $codigo)
                ->get();

            // Log de éxito
            Log::info('Guía de ingreso consultada correctamente', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'consultarGuia',
                'Codigo' => $codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'guiaIngreso' => $guiaIngreso,
                'detalleGuia' => $detalleGuia,
            ], 200);
        } catch (\Exception $e) {

            // Log del error
            Log::error('Ocurrió un error al consultar la guía de ingreso', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'consultarGuia',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al consultar la guia de ingreso', 'bd' => $e->getMessage()], 500);
        }
    }


    public function actualizarGuiaIngreso(Request $request)
    {
        DB::beginTransaction();
        try {

            // 1. Validar si la guía de ingreso existe
            $guiaIngreso = DB::table('guiaingreso')
                ->where('Codigo', $request->Codigo)
                ->first();

            if (!$guiaIngreso) {
                Log::warning('La guía de ingreso no existe.', [
                    'Controlador' => 'GuiaIngresoController',
                    'Metodo' => 'actualizarGuiaIngreso',
                    'Codigo' => $request->Codigo
                ]);
                return response()->json(['error' => 'La guía de ingreso no existe.'], 404);
            }

            // 2. Validar si la guía de ingreso está vigente
            if ($guiaIngreso->Vigente == 0) {
                Log::warning('No se puede actualizar una Guía de Ingreso en estado Inactivo.', [
                    'Controlador' => 'GuiaIngresoController',
                    'Metodo' => 'actualizarGuiaIngreso',
                    'Codigo' => $request->Codigo
                ]);
                return response()->json(['error' => 'No se puede actualizar una Guía de Ingreso en estado Inactivo.'], 400);
            }

            // -------------------------------- 
            // 1. Buscar si hay algún lote activo vinculado a los detalles de esta guía
            $tieneLotesActivos = DB::table('lote as l')
                ->join('detalleguiaingreso as dgi', 'l.CodigoDetalleIngreso', '=', 'dgi.Codigo')
                ->where('dgi.CodigoGuiaRemision', $request->Codigo)
                ->where('l.Vigente', 1)
                ->exists();

            // 2. Validar si tiene lotes activos
            if ($tieneLotesActivos) {
                Log::warning('No se puede dar de baja a esta guía de ingreso porque tiene lotes activos.', [
                    'Controlador' => 'GuiaIngresoController',
                    'Metodo' => 'actualizarGuiaIngreso',
                    'Codigo' => $request->Codigo
                ]);
                return response()->json([
                    'error' => 'No se puede dar de baja a esta guía de ingreso porque tiene lotes activos.'
                ], 400);
            }
            // 3. Dar de baja a la guía
            if ($request->Vigente == 0) {

                DB::table('guiaingreso')
                    ->where('Codigo', $request->Codigo)
                    ->update(['Vigente' => 0]);
            }

            // Log de éxito
            Log::info('Guía de ingreso anulada correctamente.', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'actualizarGuiaIngreso',
                'Codigo' => ($request->Codigo),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            DB::commit();
            return response()->json(['mensaje' => 'Guía de ingreso anulada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log del error
            Log::error('Ocurrió un error al actualizar la guía de ingreso', [
                'Controlador' => 'GuiaIngresoController',
                'Metodo' => 'actualizarGuiaIngreso',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json(['error' => 'Ocurrió un error al actualizar la guia de ingreso', 'bd' => $e->getMessage()], 500);
        }
    }
}
