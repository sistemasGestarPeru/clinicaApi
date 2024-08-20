<?php

namespace App\Http\Controllers\API\Venta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\DetalleVenta;
use App\Models\Recaudacion\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
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

    public function registrarVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesVentaData = $request->input('detalleVenta');
        $ventaData = $request->input('venta');

        if (isset($ventaData['CodigoPersona']) && $ventaData['CodigoPersona'] == 0) {
            $ventaData['CodigoPersona'] = null;
        }

        if (isset($ventaData['CodigoClienteEmpresa']) && $ventaData['CodigoClienteEmpresa'] == 0) {
            $ventaData['CodigoClienteEmpresa'] = null;
        }


        if (isset($ventaData['CodigoContratoProducto']) && $ventaData['CodigoContratoProducto'] == 0) {
            $ventaData['CodigoContratoProducto'] = null;
        }

        $ventaData['Fecha'] = $fecha;
        $ventaData['Estado'] = 'C';
        $ventaData['EstadoFactura'] = 'M';

        DB::beginTransaction();
        try {

            $venta = Venta::create($ventaData);
            $codigoVenta = $venta->Codigo;

            foreach ($detallesVentaData as $detalle) {
                $detalle['CodigoVenta'] = $codigoVenta;
                DetalleVenta::create($detalle);
            }

            DB::commit();
            return response()->json(['message' => 'Venta registrada correctamente', 'codigo' => $codigoVenta], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'data' => $ventaData, 'detalle' => $detallesVentaData], 500);
        }
    }

    public function consultarDatosContratoProducto(Request $request)
    {
        $idContrato = $request->input('idContrato');
        try {
            // Consulta del contrato
            $contrato = DB::table('contratoproducto as cp')
                ->leftJoin('personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'cp.CodigoClienteEmpresa')
                ->where('cp.Codigo', $idContrato)
                ->select(
                    'cp.Codigo',
                    'cp.NumContrato',
                    DB::raw('DATE(cp.Fecha) as Fecha'),
                    DB::raw(
                        "
                        CASE 
                            WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                            WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                            ELSE 'No identificado'
                        END as NombreCompleto"
                    ),
                    DB::raw(
                        "
                        CASE 
                            WHEN p.Codigo IS NOT NULL THEN CONCAT(td.Siglas, ': ', p.NumeroDocumento)
                            WHEN ce.Codigo IS NOT NULL THEN ce.Ruc
                            ELSE 'Documento no disponible'
                        END as DocumentoCompleto"
                    ),
                    DB::raw('COALESCE(p.Codigo, 0) as CodigoPaciente'),
                    DB::raw('COALESCE(ce.Codigo, 0) as CodigoEmpresa'),
                    DB::raw(
                        "
                        CASE
                            WHEN p.Codigo IS NOT NULL THEN p.CodigoTipoDocumento
                            ELSE -1
                        END as CodTipoDoc"
                    )
                )
                ->first();


            // Consulta del detalle del contrato
            $detalle = DB::table('detallecontrato as dc')
                ->join('producto as p', 'p.Codigo', '=', 'dc.CodigoProducto')
                ->select(
                    'dc.MontoTotal',
                    'dc.Cantidad',
                    'dc.Descripcion',
                    'dc.CodigoProducto',
                    'p.TipoGravado',
                    DB::raw("(CASE WHEN p.TipoGravado = 'A' THEN ROUND(dc.MontoTotal - (dc.MontoTotal / (1 + 0.18)), 2) ELSE 0 END) as MontoIGV")
                )
                ->where('dc.CodigoContrato', $idContrato)
                ->get();

            // Convertir los valores a números en lugar de cadenas
            $detalle = $detalle->map(function ($item) {
                $item->MontoTotal = (float) $item->MontoTotal;
                $item->MontoIGV = (float) $item->MontoIGV;
                return $item;
            });

            return response()->json(['contrato' => $contrato, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buscarCliente(Request $request)
    {
        $tipoDocumento = $request->input('tipoDocumento');
        $numDocumento = $request->input('numDocumento');
        $codSede = $request->input('codSede');
        $codDocumento = $request->input('codDocumento');

        try {
            if ($tipoDocumento !== 'RUC') {
                $cliente = DB::table('personas as p')
                    ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'p.CodigoDepartamento')
                    ->where('s.Codigo', $codSede)
                    ->where('s.Vigente', 1)
                    ->where('p.NumeroDocumento', $numDocumento)
                    ->where('p.CodigoTipoDocumento', $codDocumento)
                    ->where('p.Vigente', 1)
                    ->select('p.Codigo', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as NombreCompleto"), DB::raw('1 as TipoCliente'))
                    ->orderBy('p.Codigo')
                    ->first();
            } else {
                $cliente = DB::table('clienteempresa as ce')
                    ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'ce.CodigoDepartamento')
                    ->where('ce.RUC', $numDocumento)
                    ->where('ce.Vigente', 1)
                    ->where('s.Codigo', $codSede)
                    ->select('ce.Codigo', 'ce.RazonSocial as NombreCompleto', DB::raw('0 as TipoCliente'))
                    ->first();
            }
            return response()->json($cliente, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function listarVentas()
    {
        try {
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
