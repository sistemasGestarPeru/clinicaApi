<?php

namespace App\Http\Controllers\API\ContratoProducto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\ContratoProducto\RegistrarContratoProductoRequest;
use App\Http\Requests\Recaudacion\ContratoProducto\RegistrarDetalleContratoRequest;
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
    
       // ->where(DB::raw("DATE(cp.Fecha)"), $fecha)

        try {
            $contratos = DB::table('clinica_db.contratoproducto as cp')
            ->leftJoin('clinica_db.personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
            ->leftJoin('clinica_db.clienteempresa as ce', 'ce.Codigo', '=', 'cp.CodigoClienteEmpresa')
            ->where('cp.CodigoSede', 1) // Filtro por CódigoSede
            ->where('cp.Vigente', $codigoSede) // Filtro por Vigente
            ->select(
                'cp.Codigo as Codigo',
                DB::raw("DATE(cp.Fecha) as Fecha"),
                'cp.Total as Total',
                'cp.TotalPagado as TotalPagado',
                DB::raw("
                    CASE
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                        WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                        ELSE 'No identificado'
                    END as NombrePaciente
                ")
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
    
        DB::beginTransaction();
        try {
            // Verificar si hay ventas asociadas
            $documentoVenta = DB::table('documentoventa as dv')
                ->join('contratoproducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
                ->where('cp.Codigo', $codigo)
                ->where('dv.Vigente', 1)
                ->exists();
    
            // Si no hay ventas asociadas, actualizar el contrato y compromisos
            if (!$documentoVenta) {
                DB::table('contratoproducto')
                    ->where('Codigo', $codigo)
                    ->update(['Vigente' => 0]);
    
                DB::table('compromisocontrato')
                    ->where('CodigoContrato', $codigo)
                    ->where('Vigente', 1)
                    ->update(['Vigente' => 0]);
    
                DB::commit();
                
                return response()->json([
                    'message' => 'Contrato anulado correctamente',
                    'id' => 1
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'message' => 'No se puede anular contrato porque tiene documentos de venta asociados.',
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
                DB::raw("
                    CASE
                        WHEN cp.CodigoClienteEmpresa IS NULL THEN CONCAT(pPac.Nombres, ' ', pPac.Apellidos)
                        ELSE ce.RazonSocial
                    END AS Nombre
                "),
                DB::raw("
                    CASE
                        WHEN cp.CodigoClienteEmpresa IS NULL THEN CONCAT(td.Nombre, ': ', pPac.NumeroDocumento)
                        ELSE ce.RUC
                    END AS Documento
                "),
                DB::raw("CONCAT(pMed.Nombres, ': ', pMed.Apellidos) AS Medico")
            )
            ->join('personas as pMed', 'pMed.Codigo', '=', 'cp.CodigoMedico')
            ->leftJoin('personas as pPac', 'pPac.Codigo', '=', 'cp.CodigoPaciente')
            ->leftJoin('clienteEmpresa as ce', 'ce.Codigo', '=', 'cp.CodigoClienteEmpresa')
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
            ->select(
                'dv.Codigo as Venta',
                'dv.CodigoContratoProducto as Contrato',
                DB::raw("CONCAT(tdv.Nombre, ' ', dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) as Documento"),
                DB::raw(
                    "CASE 
                        WHEN e.Codigo IS NOT NULL THEN mpe.Nombre 
                        WHEN p.Codigo IS NOT NULL THEN mp.Nombre 
                    END AS MedioPago"
                ),
                'dv.MontoPagado',
                DB::raw(
                    "CASE 
                        WHEN dv.CodigoMotivoNotaCredito IS NOT NULL THEN 'N' 
                        WHEN dv.Vigente = 0 THEN 'A' 
                        WHEN dv.CodigoMotivoNotaCredito IS NULL THEN 'V' 
                    END AS TipoVenta"
                )
            )
            ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
            ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
            ->leftJoin('Pago as p', 'p.Codigo', '=', 'pdv.CodigoPago')
            ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
            ->leftJoin('devolucionnotacredito as dnc', 'dnc.CodigoDocumentoVenta', '=', 'dv.Codigo')
            ->leftJoin('egreso as e', 'e.Codigo', '=', 'dnc.Codigo')
            ->leftJoin('mediopago as mpe', 'mpe.Codigo', '=', 'e.CodigoMedioPago')
            ->where('dv.CodigoContratoProducto', $codigo)
            ->orderBy('dv.Codigo', 'asc')
            ->get();

            return response()->json($ventas);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al buscar historial de ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
