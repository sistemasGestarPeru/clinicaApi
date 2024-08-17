<?php

namespace App\Http\Controllers\API\Venta;

use App\Http\Controllers\Controller;
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

    public function consultarDatosContratoProducto(Request $request)
    {
        $idContrato = $request->input('idContrato');
        try {
            // Consulta del contrato
            $contrato = DB::table('contratoproducto as cp')
                ->join('personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                ->join('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->where('cp.Codigo', $idContrato)
                ->select(
                    'cp.Codigo',
                    'cp.NumContrato',
                    DB::raw('DATE(cp.Fecha) as Fecha'), // Convertir cp.Fecha a solo tipo date
                    DB::raw('CONCAT(p.Nombres, " ", p.Apellidos) as NombreCompleto'), // Concatenar Nombres y Apellidos
                    DB::raw('CONCAT(td.Siglas, ": ", p.NumeroDocumento) as DocumentoCompleto') // Concatenar Siglas: NumeroDocumento
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
}
