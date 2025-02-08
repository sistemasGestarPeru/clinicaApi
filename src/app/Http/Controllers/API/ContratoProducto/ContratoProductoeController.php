<?php

namespace App\Http\Controllers\API\ContratoProducto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\ContratoProducto\RegistrarContratoProductoRequest;
use App\Http\Requests\Recaudacion\ContratoProducto\RegistrarDetalleContratoRequest;
use App\Models\Recaudacion\AnulacionContrato;
use App\Models\Recaudacion\CompromisoContrato;
use App\Models\Recaudacion\ContratoProducto;
use App\Models\Recaudacion\DetalleContrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContratoProductoeController extends Controller
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

    public function buscarProducto(Request $request)
    {
        $nombreProducto = $request->input('nombreProducto');
        $sede = $request->input('sede');

        try {
            $producto = DB::table('sedeproducto as sp')
            ->select(
                'p.Codigo',
                'p.Nombre',
                'sp.Precio as Monto',
                'p.Tipo',
                'tg.Tipo as TipoGravado',
                'sp.Stock'
            )
            ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
            ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
            ->where('sp.CodigoSede', $sede) // Filtro por CódigoSede
            ->where('sp.Vigente', 1) // Filtro por Vigente en sedeproducto
            ->where('p.Vigente', 1) // Filtro por Vigente en producto
            ->where('tg.Vigente', 1) // Filtro por Vigente en tipogravado
            ->where('p.Nombre', 'LIKE', "%{$nombreProducto}%") // Filtro por Nombre
            ->get();

            return response()->json($producto);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function registrarContratoProducto(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesContrato = $request->input('detalleContrato');
        $contratoProductoData = $request->input('contratoProducto');
        $compromisoContrato = $request->input('compromisoContrato');

        //Validar Contrato
        $contratoValidar = Validator::make($contratoProductoData, (new RegistrarContratoProductoRequest())->rules());
        $contratoValidar->validate();

        //validar DetalleProductos
        $detalleContratoValidar = Validator::make(
            ['detalleContrato' => $detallesContrato],
            (new RegistrarDetalleContratoRequest())->rules()
        );

        $detalleContratoValidar->validate();

        if (isset($contratoProductoData['CodigoPaciente']) && $contratoProductoData['CodigoPaciente'] == 0) {
            $contratoProductoData['CodigoPaciente'] = null;
        }

        if (isset($contratoProductoData['CodigoPaciente02']) && $contratoProductoData['CodigoPaciente02'] == 0) {
            $contratoProductoData['CodigoPaciente02'] = null;
        }

        if (isset($contratoProductoData['CodigoClienteEmpresa']) && $contratoProductoData['CodigoClienteEmpresa'] == 0) {
            $contratoProductoData['CodigoClienteEmpresa'] = null;
        }

        // Obtener el CódigoSede desde los datos del contrato
        $codigoSede = $contratoProductoData['CodigoSede'];

        // Obtener el último NumContrato para la sede específica y sumarle 1
        $ultimoNumContrato = ContratoProducto::where('CodigoSede', $codigoSede)
            ->max('NumContrato');
        $contratoProductoData['NumContrato'] = $ultimoNumContrato ? $ultimoNumContrato + 1 : 1;

        $contratoProductoData['Fecha'] = $fecha;

        DB::beginTransaction();
        try {
            // Crear el ContratoProducto
            $Contrato = ContratoProducto::create($contratoProductoData);

            // Crear los DetalleContrato
            foreach ($detallesContrato as $detalle) {
                $detalle['CodigoContrato'] = $Contrato->Codigo;
                DetalleContrato::create($detalle);
            }

            if (!empty($request->input('compromisoContrato')) && count($compromisoContrato) > 0) {
                foreach ($compromisoContrato as $compromiso) {
                    $compromiso['CodigoContrato'] = $Contrato->Codigo;
                    CompromisoContrato::create($compromiso);
                }
            }

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'message' => 'Contrato registrado correctamente'
            ], 200);
        } catch (\Exception $e) {
            // Hacer rollback en caso de error
            DB::rollBack();

            return response()->json([
                'message' => 'Error al registrar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function buscarContratoProducto(Request $request)
    {
        $fecha = $request->input('fecha');
        $codigoSede = $request->input('codigoSede');
        $nombre = $request->input('nombre');
        $documento = $request->input('documento');
       // ->where(DB::raw("DATE(cp.Fecha)"), $fecha)

        try {
            $contratos = DB::table('clinica_db.contratoproducto as cp')
            ->join('clinica_db.personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
            ->where('cp.CodigoSede', $codigoSede) // Filtro por CódigoSede
            ->where(DB::raw("DATE(cp.Fecha)"), $fecha)
            ->when($nombre, function ($query) use ($nombre) {
                $query->where(function ($q) use ($nombre) {
                    $q->where('p.Nombres', 'LIKE', "%$nombre%")
                        ->orWhere('p.Apellidos', 'LIKE', "%$nombre%");
                });
            })
            ->when($documento, function ($queryD) use ($documento) {
                $queryD->where(function ($q) use ($documento) {
                    $q->where('p.NumeroDocumento', 'LIKE', "%$documento%");
                });
            })
            ->orderByDesc('cp.Codigo')

            ->select(
                'cp.Codigo as Codigo',
                DB::raw("DATE(cp.Fecha) as Fecha"),
                'cp.Total as Total',
                'cp.TotalPagado as TotalPagado',
                DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as NombrePaciente"),
                'cp.Vigente as Vigente'
            )
            ->get();

            return response()->json($contratos);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar contrato producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function anularContrato(Request $request) 
    {
        $codigo = $request->input('codigoContrato');
        $anularContrato = $request->input('anularContrato');
    
        DB::beginTransaction();
        try {
            // Verificar si hay documentos de venta asociados
            $existeDocumentoVenta = DB::table('documentoventa as dv')
                ->where('dv.CodigoContratoProducto', $codigo)
                ->where('dv.Vigente', 1)
                ->exists();
    
            // Sumar los montos totales de los documentos de venta
            $sumaMontoTotal = DB::table('documentoventa as dv')
                ->where('dv.CodigoContratoProducto', $codigo)
                ->sum('dv.MontoTotal');
    
            // Permitir anulación si no hay documentos o si la suma de montos es 0
            if (!$existeDocumentoVenta || $sumaMontoTotal == 0.0) {
                DB::table('contratoproducto')
                    ->where('Codigo', $codigo)
                    ->update(['Vigente' => 0]);
    
                DB::table('compromisocontrato')
                    ->where('CodigoContrato', $codigo)
                    ->where('Vigente', 1)
                    ->update(['Vigente' => 0]);
                
                $anularContrato['Codigo'] = $codigo;
                AnulacionContrato::create($anularContrato);

                DB::commit();
    
                return response()->json([
                    'message' => 'Contrato anulado correctamente',
                    'id' => 1
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'message' => 'No se puede anular contrato porque tiene documentos de venta con montos pendientes.',
                    'id' => 2
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function visualizarContrato($contrato){
            $contratoProducto = DB::table('contratoproducto as cp')
            ->select(
                DB::raw("CONCAT(pPac.Nombres, ' ', pPac.Apellidos) AS Nombre"),
                DB::raw("CONCAT(td.Nombre, ': ', pPac.NumeroDocumento) AS Documento"),
                DB::raw("CONCAT(pMed.Nombres, ': ', pMed.Apellidos) AS Medico")
            )
            ->join('personas as pMed', 'pMed.Codigo', '=', 'cp.CodigoMedico')
            ->join('personas as pPac', 'pPac.Codigo', '=', 'cp.CodigoPaciente')
            ->join('tipo_documentos as td', 'td.Codigo', '=', 'pPac.CodigoTipoDocumento')
            ->where('cp.Codigo', $contrato)
            ->first();

        $detalleContrato = DB::table('detallecontrato as dc')
            ->select('dc.CodigoProducto as Codigo', 'dc.Descripcion as Nombre', 'dc.MontoTotal as SubTotal', 'dc.Cantidad')
            ->join('producto as p', 'p.Codigo', '=', 'dc.CodigoProducto')
            ->where('dc.CodigoContrato', $contrato)
            ->get();

        return response()->json([
            'contratoProducto' => $contratoProducto,
            'detalleContrato' => $detalleContrato,
        ]);
    }


    public function historialContratoVenta($codigo){

        try{
            $ventas = DB::table('documentoventa as dv')
                ->selectRaw("
                    dv.Codigo AS Venta,
                    dv.CodigoContratoProducto AS Contrato,
                    CONCAT(tdv.Nombre, ' ', dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) AS Documento,
                    CASE 
                        WHEN e.Codigo IS NOT NULL THEN mpe.Nombre 
                        WHEN p.Codigo IS NOT NULL THEN mp.Nombre 
                    END AS MedioPago,
                    dv.MontoPagado,
                    CASE 
                        WHEN dv.CodigoMotivoNotaCredito IS NOT NULL THEN 'N' 
                        WHEN dv.Vigente = 0 THEN 'A' 
                        WHEN dv.CodigoMotivoNotaCredito IS NULL THEN 'V' 
                    END AS TipoVenta,
                    tdv.CodigoSUNAT AS CodigoSUNAT,
                    COALESCE(
                        (SELECT dv.MontoTotal + SUM(nc.MontoTotal) 
                        FROM documentoventa AS nc 
                        WHERE nc.CodigoMotivoNotaCredito IS NOT NULL 
                        AND nc.CodigoDocumentoReferencia = dv.Codigo
                        ), 
                        dv.MontoTotal
                    ) AS SaldoFinal
                ")
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
                ->leftJoin('documentoventa as NOTACREDITO', function ($join) {
                    $join->on('NOTACREDITO.CodigoDocumentoReferencia', '=', 'dv.Codigo')
                        ->whereNotNull('NOTACREDITO.CodigoMotivoNotaCredito');
                })
                ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('Pago as p', 'p.Codigo', '=', 'pdv.CodigoPago')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
                ->leftJoin('devolucionnotacredito as dnc', 'dnc.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('egreso as e', 'e.Codigo', '=', 'dnc.Codigo')
                ->leftJoin('mediopago as mpe', 'mpe.Codigo', '=', 'e.CodigoMedioPago')
                ->where('dv.CodigoContratoProducto', $codigo)
                ->orderBy('dv.Codigo', 'ASC')
                ->get();

            return response()->json($ventas);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al buscar historial de ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function contratoPDF($contrato){
        try{
            $contratoData = DB::table('contratoproducto as c')
                ->join('sedesrec as s', 's.Codigo', '=', 'c.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->join('personas as cliente', 'cliente.Codigo', '=', 'c.CodigoPaciente')
                ->join('personas as medico', 'medico.Codigo', '=', 'c.CodigoMedico')
                ->join('tipo_documentos as td', 'td.Codigo', '=', 'cliente.CodigoTipoDocumento')
                ->select(
                    'e.Nombre as empresaNombre',
                    'e.RUC as ruc',
                    'e.Direccion as direccion',
                    DB::raw("'Lambayeque' as departamento"),
                    DB::raw("'Chiclayo' as provincia"),
                    DB::raw("'Chiclayo' as distrito"),
                    's.Nombre as sede',
                    DB::raw("LPAD(c.NumContrato, 8, '0') AS numero"),
                    DB::raw("CONCAT(cliente.Nombres, ' ', cliente.Apellidos) as cliente"),
                    'td.Nombre as documentoIdentidad',
                    'cliente.NumeroDocumento as numDocumento',
                    'cliente.Direccion as clienteDireccion',
                    DB::raw("DATE(c.Fecha) AS fechaEmision"),
                    DB::raw("'Soles' as moneda"),
                    DB::raw("CONCAT(medico.Nombres, ' ', medico.Apellidos) as medico")
                )
                ->where('c.Codigo', $contrato)
                ->first();

            // Obtener detalles del contrato
            $detalleContrato = DB::table('detallecontrato as dc')
                ->select(
                    'dc.Descripcion as descripcion',
                    'dc.MontoTotal as monto',
                    'dc.Cantidad as cantidad',
                    DB::raw('(dc.MontoTotal * dc.Cantidad) as subTotal')
                )
                ->where('dc.CodigoContrato', $contrato)
                ->get();

            // Obtener compromisos de pago del contrato
            $compromisos = DB::table('compromisocontrato')
                ->select('Fecha as fecha', 'Monto as monto')
                ->where('CodigoContrato', $contrato)
                ->get();

                return  response()->json([
                    'contrato' => $contratoData,
                    'detalle' => $detalleContrato,
                    'compromisos' => $compromisos
                ]);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
