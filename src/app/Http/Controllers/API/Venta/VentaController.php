<?php

namespace App\Http\Controllers\API\Venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Http\Requests\Recaudacion\Pago\RegistrarPagoRequest;
use App\Http\Requests\Venta\RegistrarDetalleVentaRequest;
use App\Http\Requests\Venta\RegistrarVentaRequest;
use App\Models\Recaudacion\Anulacion;
use App\Models\Recaudacion\DetalleVenta;
use App\Models\Recaudacion\DevolucionNotaCredito;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\Pago;
use App\Models\Recaudacion\PagoDocumentoVenta;
use App\Models\Recaudacion\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use TCPDF;

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

    public function registrarPagoVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $pagoData = $request->input('pago');
        $ventaData = $request->input('venta');

        if (!$ventaData) {
            return response()->json(['error' => 'No se ha encontrado la venta.'], 400);
        }

        if ($pagoData['CodigoMedioPago'] == 1) {
            $pagoData['Fecha'] = $fecha;
            $pagoData['CodigoCuentaBancaria'] = null;
        }

        $pagoValidator = Validator::make($pagoData, (new RegistrarPagoRequest())->rules());
        $pagoValidator->validate();

        DB::beginTransaction();

        try {

            $pago = Pago::create($pagoData);
            $codigoPago = $pago->Codigo;

            PagoDocumentoVenta::create([
                'CodigoPago' => $codigoPago,
                'CodigoDocumentoVenta' => $ventaData,
                'Monto' => $pagoData['Monto'],
            ]);

            DB::table('documentoventa')
                ->where('Codigo', $ventaData)
                ->increment('MontoPagado', $pagoData['Monto']);

            DB::commit();
            return response()->json(['message' => 'Pago registrada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se puedo registrar el pago.'], 500);
        }
    }


    public function registrarNotaCredito(Request $request)
    {

        $ventaData = $request->input('venta');
        $detallesVentaData = $request->input('detalleVenta');
        $dataEgreso = $request->input('egreso');

        //Validar Venta
        $ventaValidator = Validator::make($ventaData, (new RegistrarVentaRequest())->rules());
        $ventaValidator->validate();

        if (isset($ventaData['CodigoAutorizador']) && $ventaData['CodigoAutorizador'] == 0) {
            $ventaData['CodigoAutorizador'] = null;
        }

        if (isset($ventaData['CodigoClienteEmpresa']) && $ventaData['CodigoClienteEmpresa'] == 0) {
            $ventaData['CodigoClienteEmpresa'] = null;
        }
        if (isset($ventaData['CodigoPersona']) && $ventaData['CodigoPersona'] == 0) {
            $ventaData['CodigoPersona'] = null;
        }

        //validar Detalles de Venta
        $detalleVentaValidar = Validator::make(
            ['detalleVenta' => $detallesVentaData],
            (new RegistrarDetalleVentaRequest())->rules()
        );
        $detalleVentaValidar->validate();

        //Validar Egreso
        if ($dataEgreso['CodigoCuentaOrigen'] == 0) {
            $dataEgreso['CodigoCuentaOrigen'] = null;
        }

        $dataEgreso['CodigoCaja'] = $ventaData['CodigoCaja'];
        $dataEgreso['CodigoTrabajador'] = $ventaData['CodigoTrabajador'];

        //Validar Egreso
        $egresoValidator = Validator::make($dataEgreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();


        DB::beginTransaction();

        try {
            $ventaCreada = Venta::create($ventaData);

            foreach ($detallesVentaData as $detalle) {
                $detalle['CodigoVenta'] = $ventaCreada->Codigo;
                DetalleVenta::create($detalle);
            }

            if ($dataEgreso['CodigoMedioPago'] == 1) {
                $egreso['CodigoCuentaOrigen'] = null;
            }

            $egresoCreado = Egreso::create($dataEgreso);

            $dataDevolucion = [
                'Codigo' => $egresoCreado->Codigo,
                'CodigoDocumentoVenta' => $ventaCreada->Codigo,
            ];

            DevolucionNotaCredito::create($dataDevolucion);

            DB::commit();

            return response()->json(['message' => 'Nota de Crédito registrada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function registrarVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $ventaData = $request->input('venta');
        $detallesVentaData = $request->input('detalleVenta');
        $pagoData = $request->input('pago');

        //Validar Venta
        $ventaValidator = Validator::make($ventaData, (new RegistrarVentaRequest())->rules());
        $ventaValidator->validate();

        if (isset($ventaData['CodigoAutorizador']) && $ventaData['CodigoAutorizador'] == 0) {
            $ventaData['CodigoAutorizador'] = null;
        }

        if (isset($ventaData['CodigoPersona']) && $ventaData['CodigoPersona'] == 0) {
            $ventaData['CodigoPersona'] = null;
        }

        if (isset($ventaData['CodigoClienteEmpresa']) && $ventaData['CodigoClienteEmpresa'] == 0) {
            $ventaData['CodigoClienteEmpresa'] = null;
        }

        //validar Detalles de Venta
        $detalleVentaValidar = Validator::make(
            ['detalleVenta' => $detallesVentaData],
            (new RegistrarDetalleVentaRequest())->rules()
        );
        $detalleVentaValidar->validate();


        //Validar Pago
        if ($pagoData) {
            $pagoData['Monto'] = $ventaData['MontoPagado'];
            $pagoData['CodigoCaja'] = $ventaData['CodigoCaja'];
            $pagoData['CodigoTrabajador'] = $ventaData['CodigoTrabajador'];

            if ($pagoData['CodigoMedioPago'] == 1) {
                $pagoData['Fecha'] = $fecha;
                $pagoData['CodigoCuentaBancaria'] = null;
            }
            $pagoValidator = Validator::make($pagoData, (new RegistrarPagoRequest())->rules());
            $pagoValidator->validate();
        }

        DB::beginTransaction();
        try {

            $ventaCreada = Venta::create($ventaData);

            foreach ($detallesVentaData as $detalle) {
                $detalle['CodigoVenta'] = $ventaCreada->Codigo;
                DetalleVenta::create($detalle);
            }

            if ($pagoData) {
                $pagoCreado = Pago::create($pagoData);
                PagoDocumentoVenta::create([
                    'CodigoPago' => $pagoCreado->Codigo,
                    'CodigoDocumentoVenta' => $ventaCreada->Codigo,
                    'Monto' => $pagoData['Monto']
                ]);
            }

            if (isset($ventaData['CodigoContratoProducto']) && $ventaData['CodigoContratoProducto']) {
                DB::table('contratoproducto')
                    ->where('Codigo', $ventaData['CodigoContratoProducto'])
                    ->increment('TotalPagado', $pagoData['Monto']);
            }
            DB::commit();

            return response()->json(['message' => 'Venta registrada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarDocumentoVenta(Request $request)
    {

        $CodVenta = $request->input('venta');
        $sede = $request->input('sede');
        try {
            $venta = DB::table('documentoventa as dv')
                ->select(
                    'dv.Codigo',
                    DB::raw('DATE(dv.Fecha) as Fecha'),
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                        WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                        ELSE 'No identificado'
                    END AS NombreCompleto
                "),
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(td.Siglas, ': ', p.NumeroDocumento)
                        WHEN ce.Codigo IS NOT NULL THEN ce.Ruc
                        ELSE 'Documento no disponible'
                    END AS DocumentoCompleto
                "),
                    DB::raw('COALESCE(p.Codigo, 0) AS CodigoPersona'),
                    DB::raw('COALESCE(ce.Codigo, 0) AS CodigoEmpresa'),
                    DB::raw("
                    CASE
                        WHEN p.Codigo IS NOT NULL THEN p.CodigoTipoDocumento
                        ELSE -1
                    END AS CodTipoDoc
                "),
                    'dv.CodigoMedico',
                    DB::raw("CONCAT(medico.Nombres, ' ', medico.Apellidos) AS NombreMedico"),
                    DB::raw('COALESCE(dv.CodigoPaciente, 0) AS CodigoPaciente'),
                    DB::raw("COALESCE(CONCAT(paciente.Nombres, ' ', paciente.Apellidos), '') AS NombrePaciente"),
                    DB::raw("COALESCE(CONCAT(tdPaciente.Siglas, ': ', paciente.NumeroDocumento), '') AS DocumentoPaciente"),
                    'dv.CodigoTipoDocumentoVenta',
                    'tdv.Nombre as TipoDocumentoVenta',
                    'dv.Serie',
                    'dv.Numero',
                    'cp.Codigo as CodigoContrato',
                    'cp.NumContrato',
                    DB::raw('DATE(cp.Fecha) as FechaContrato')
                )
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
                ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
                ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
                ->leftJoin('personas as paciente', 'paciente.Codigo', '=', 'dv.CodigoPaciente')
                ->leftJoin('personas as medico', 'medico.Codigo', '=', 'dv.CodigoMedico')
                ->leftJoin('tipo_documentos as tdPaciente', 'tdPaciente.Codigo', '=', 'paciente.CodigoTipoDocumento')
                ->leftJoin('contratoProducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
                ->where('dv.Codigo', $CodVenta)
                ->first();

            $detalle = DB::table('detalledocumentoventa as ddv')
                ->join('sedeproducto as sp', 'sp.CodigoProducto', '=', 'ddv.CodigoProducto')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->where('ddv.CodigoVenta', $CodVenta)
                ->where('sp.CodigoSede', $sede)
                ->select(
                    'ddv.MontoTotal',
                    DB::raw("CASE WHEN p.Tipo = 'B' THEN ddv.Cantidad WHEN p.Tipo = 'S' THEN 1 END AS Cantidad"),
                    'ddv.Descripcion',
                    'ddv.CodigoProducto',
                    'p.Tipo',
                    DB::raw("CASE WHEN tg.Tipo = 'G' THEN ROUND(ddv.MontoTotal - (ddv.MontoTotal / (1 + (tg.Porcentaje / 100))), 2) ELSE 0 END AS MontoIGV"),
                    'tg.Tipo AS TipoGravado',
                    'tg.Codigo AS CodigoTipoGravado',
                    'tg.Porcentaje'
                )
                ->get();


            return response()->json(['venta' => $venta, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarDatosContratoProducto(Request $request)
    {
        $idContrato = $request->input('idContrato');
        try {
            $contrato = DB::table('contratoproducto as CONTRATO')
            ->join('personas as PACIENTE', 'PACIENTE.Codigo', '=', 'CONTRATO.CodigoPaciente')
            ->join('personas as MEDICO', 'MEDICO.Codigo', '=', 'CONTRATO.CodigoMedico')
            ->join('tipo_documentos as tdPaciente', 'tdPaciente.Codigo', '=', 'PACIENTE.CodigoTipoDocumento')
            ->leftJoin('documentoventa as VENTA', function ($join) {
                $join->on('VENTA.CodigoContratoProducto', '=', 'CONTRATO.Codigo')
                     ->where('VENTA.Vigente', '=', 1);
            })
            ->leftJoin('personas as CLIENTE', 'CLIENTE.Codigo', '=', 'VENTA.CodigoPersona')
            ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'CLIENTE.CodigoTipoDocumento')
            ->leftJoin('clienteempresa as EMPRESA', 'EMPRESA.Codigo', '=', 'VENTA.CodigoClienteEmpresa')
            ->where('CONTRATO.Codigo', $idContrato)
            ->select(
                'CONTRATO.Codigo',
                'CONTRATO.NumContrato',
                DB::raw('DATE(CONTRATO.Fecha) as Fecha'),
                'PACIENTE.Codigo as CodigoPaciente',
                DB::raw("CONCAT(PACIENTE.Nombres, ' ', PACIENTE.Apellidos) as NombrePaciente"),
                DB::raw("CONCAT(tdPaciente.Siglas, ': ', PACIENTE.NumeroDocumento) as DocumentoPaciente"),
                'CONTRATO.CodigoMedico',
                DB::raw("CONCAT(MEDICO.Nombres, ' ', MEDICO.Apellidos) as NombreMedico"),
                DB::raw("COALESCE(VENTA.CodigoClienteEmpresa, 0) as CodigoEmpresa"),
                DB::raw("COALESCE(VENTA.CodigoPersona, 0) as CodigoPersona"),
                DB::raw("COALESCE(CONCAT(CLIENTE.Nombres, ' ', CLIENTE.Apellidos), EMPRESA.RazonSocial, '') as NombreCompleto"),
                DB::raw("COALESCE(CONCAT(td.Siglas, ': ', CLIENTE.NumeroDocumento), CONCAT( 'RUC' , ': ' , EMPRESA.Ruc), '') as DocumentoCompleto")
            )
            ->first();
        

            $detalle = DB::table('Producto as P')
                ->joinSub(
                    DB::table('DetalleContrato as DC')
                        ->leftJoinSub(
                            DB::table('DocumentoVenta as DV')
                                ->join('DetalleDocumentoVenta as DDV', 'DV.Codigo', '=', 'DDV.CodigoVenta')
                                ->where('DV.CodigoContratoProducto', $idContrato)
                                ->where('DV.Vigente', 1)
                                ->groupBy('DDV.CodigoProducto')
                                ->select(
                                    'DDV.CodigoProducto',
                                    DB::raw('SUM(DDV.Cantidad) AS CantidadBoleteada'),
                                    DB::raw('SUM(DDV.MontoTotal) AS MontoBoleteado')
                                ),
                            'Bol',
                            'Bol.CodigoProducto',
                            '=',
                            'DC.CodigoProducto'
                        )
                        ->where('DC.CodigoContrato', $idContrato)
                        ->groupBy('DC.CodigoProducto', 'DC.Descripcion', 'DC.Codigo')
                        ->select(
                            'DC.CodigoProducto',
                            'DC.Descripcion',
                            'DC.Codigo',
                            DB::raw('SUM(DC.Cantidad) - COALESCE(Bol.CantidadBoleteada, 0) AS Cantidad'),
                            DB::raw('SUM(DC.MontoTotal) - COALESCE(Bol.MontoBoleteado, 0) AS Monto')
                        ),
                    'S',
                    'P.Codigo',
                    '=',
                    'S.CodigoProducto'
                )
                ->join('SedeProducto as SP', 'SP.CodigoProducto', '=', 'P.Codigo')
                ->join('TipoGravado as TG', 'TG.Codigo', '=', 'SP.CodigoTipoGravado')
                ->where('S.Monto', '>', 0)
                ->orderBy('S.Descripcion')
                ->select(
                    'S.CodigoProducto',
                    'S.Descripcion',
                    'P.Tipo',
                    DB::raw('CASE WHEN P.Tipo = "B" THEN S.Cantidad ELSE 1 END AS Cantidad'),
                    'S.Monto as MontoTotal',
                    'TG.Tipo AS TipoGravado',
                    'TG.Porcentaje AS Porcentaje',
                    'TG.Codigo AS CodigoTipoGravado'
                )
                ->get();

            return response()->json(['contrato' => $contrato, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buscarCliente(Request $request)
    {
        $numDocumento = $request->input('numDocumento');
        $codSede = $request->input('codSede');
        $codDocumento = $request->input('codDocumento');

        $primerosDosDigitos = substr($numDocumento, 0, 2);
        try {
            if ($primerosDosDigitos == '20') {
                $cliente = DB::table('clienteempresa as ce')
                    ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'ce.CodigoDepartamento')
                    ->where('ce.RUC', $numDocumento)
                    ->where('ce.Vigente', 1)
                    ->where('s.Codigo', $codSede)
                    ->select('ce.Codigo', 'ce.RazonSocial as NombreCompleto', DB::raw('0 as TipoCliente'))
                    ->first();
            } else {
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
            }
            return response()->json($cliente, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function buscarProductos(Request $request)
    {

        $nombreProducto = $request->input('nombreProducto');
        $sede = $request->input('codigoSede');
        $tipo = $request->input('tipo');

        try {
            $productos = DB::table('sedeproducto as sp')
                ->select(
                    'p.Codigo',
                    'p.Nombre',
                    'sp.Precio as Monto',
                    'p.Tipo',
                    'tg.Tipo as TipoGravado',
                    'tg.Codigo as CodigoTipoGravado',
                    'tg.Porcentaje',
                    'tg.CodigoSUNAT',
                    'sp.Stock'
                )
                ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->where('sp.CodigoSede', $sede) // Filtro por CódigoSede
                ->where('sp.Vigente', 1) // Filtro por Vigente en sedeproducto
                ->where('p.Vigente', 1) // Filtro por Vigente en producto
                ->where('tg.Vigente', 1) // Filtro por Vigente en tipogravado
                ->where('p.Nombre', 'LIKE', "%{$nombreProducto}%") // Filtro por Nombre
                ->where(function ($query) use ($tipo) {
                    $query->where('p.Tipo', $tipo) // Filtro por Tipo
                        ->orWhereNotExists(function ($subquery) use ($tipo) {
                            $subquery->select(DB::raw(1))
                                ->from('producto')
                                ->where('Tipo', $tipo)
                                ->where('Vigente', 1); // Verificación de existencia
                        });
                })
                ->get();
            return response()->json($productos, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
    public function buscarVenta(Request $request)
    {
    try {
        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d');

        $fecha = $request->input('fecha');
        $codigoSede = $request->input('codigoSede');
        $nombre = $request->input('nombre');

        $venta = DB::table('documentoventa as dv')
            ->selectRaw("
                dv.Vigente,
                dv.Codigo,
                dv.CodigoTipoDocumentoVenta AS TipoDoc,
                CONCAT(tdv.Nombre, ' ', dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) AS Documento,
                DATE(dv.Fecha) AS Fecha,
                dv.MontoTotal,
                dv.MontoPagado,
                CASE 
                    WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                    WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                    ELSE 'No identificado'
                END AS NombreCliente,
                CASE 
                    WHEN (dv.CodigoMotivoNotaCredito IS NULL AND dv.CodigoContratoProducto IS NULL) THEN 'V'
                    WHEN (dv.CodigoMotivoNotaCredito IS NULL AND dv.CodigoContratoProducto IS NOT NULL) THEN 'C'
                    WHEN dv.CodigoMotivoNotaCredito IS NOT NULL THEN 'N'
                END AS TipoVenta,
                CASE 
                    WHEN tdv.CodigoSUNAT IS NOT NULL THEN true
                    ELSE false
                END AS Canje,
                COALESCE((
                    SELECT dv.MontoTotal + SUM(nc.MontoTotal)
                    FROM documentoventa AS nc 
                    WHERE nc.CodigoMotivoNotaCredito IS NOT NULL 
                    AND nc.CodigoDocumentoReferencia = dv.Codigo
                ), dv.MontoTotal) AS SaldoFinal,
                CASE 
                    WHEN tdv.CodigoSUNAT = '03' AND DATEDIFF(DATE(?), DATE(dv.Fecha)) <= 7 THEN '1'
                    WHEN tdv.CodigoSUNAT = '01' AND DATEDIFF(DATE(?), DATE(dv.Fecha)) <= 3 THEN '1'
                    WHEN tdv.CodigoSUNAT = null THEN '1'
                    ELSE '0'
                END AS Anular
            ", [$fechaActual, $fechaActual])
            ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
            ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
            ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
            ->where('dv.CodigoSede', $codigoSede)
            ->when($fecha, fn($query) => $query->whereDate('dv.Fecha', $fecha))
            ->when($nombre, function ($query) use ($nombre) {
                $query->where(function ($q) use ($nombre) {
                    $q->where('p.Nombres', 'LIKE', "%$nombre%")
                        ->orWhere('p.Apellidos', 'LIKE', "%$nombre%")
                        ->orWhere('ce.RazonSocial', 'LIKE', "%$nombre%");
                });
            })
            ->orderByDesc('dv.Codigo')
            ->get();

        return response()->json($venta);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'msg' => 'Error al buscar Venta'], 500);
    }
}


    public function consultarSerieNotaCredito(Request $request)
    {
        $sede = $request->input('sede');
        $producto = $request->input('tipProd');
        $tipoDoc = $request->input('tipoDoc');

        try {

            $result = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->select('ldv.Codigo', 'ldv.Serie')
                ->where('ldv.CodigoSede', $sede)
                ->where('ldv.Vigente', 1)
                ->where('ldv.TipoProducto', $producto)
                ->where('tdv.CodigoSUNAT', '07')
                ->get();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarSerie(Request $request)
    {
        $sede = $request->input('sede');
        $tipoDocumento = $request->input('tipoDocumento');

        try {


            $result = DB::table('localdocumentoventa as ldv')
                ->select('Codigo', 'Serie')
                ->where('ldv.CodigoSede', $sede)
                ->where('ldv.CodigoTipoDocumentoVenta', $tipoDocumento)
                ->where('ldv.Vigente', 1)
                ->get();



            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function consultarTipoProducto(Request $request)
    {

        $codigo = $request->input('codigo');
        try {

            $result = DB::table('localdocumentoventa as ldv')
                ->select([
                    'ldv.TipoProducto as Codigo',
                    DB::raw("
                CASE 
                    WHEN ldv.TipoProducto = 'B' THEN 'Bien'
                    WHEN ldv.TipoProducto = 'S' THEN 'Servicio'
                    WHEN ldv.TipoProducto = 'T' THEN 'Todo'
                    ELSE 'Desconocido'
                END AS TipoProducto
                ")
                ])
                ->join('sedesrec as s', 's.Codigo', '=', 'ldv.CodigoSede')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->where('ldv.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('tdv.Vigente', 1)
                ->where('ldv.Codigo', $codigo)
                ->first();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function consultaNumDocumentoVenta(Request $request)
    {
        $sede = $request->input('sede');
        $tipoDocumento = $request->input('tipoDocumento');
        $serie = $request->input('serie');

        try {
            // Obtener el último documento de venta
            $documentoVenta = DB::table('clinica_db.documentoventa')
                ->where('CodigoTipoDocumentoVenta', $tipoDocumento)
                ->where('CodigoSede', $sede)
                ->where('Serie', $serie)
                ->where('Vigente', 1)
                ->orderBy('Codigo', 'desc')
                ->first(['Numero']);

            if ($documentoVenta) {
                // Incrementar el número y ajustar la serie si es necesario
                $nuevoNumero = $documentoVenta->Numero + 1;
            } else {
                // Si no hay registros previos, inicializar la serie y número
                $nuevoNumero = 1;
            }

            // Retornar la nueva serie y número
            return response()->json([
                'Numero' => $nuevoNumero
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function anularVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');
        $anulacionData = $request->input('Anulacion');
        $anulacionData['Fecha'] = $fecha;
        $anularPago = $anulacionData['Confirmacion'];
        $codigoVenta = $anulacionData['CodigoDocumentoVenta'];

        if (!$codigoVenta || $codigoVenta == 0) {
            return response()->json(['error' => 'No se ha encontrado la venta a anular.'], 404);
        }
        DB::beginTransaction();
        try {

            Anulacion::create($anulacionData);
            $venta = Venta::find($codigoVenta);

            if (!$venta) {
                return response()->json(['error' => 'Venta no encontrada.'], 404);
            }

            $venta->Vigente = 0;
            $venta->save();

            $pagoDocVenta = PagoDocumentoVenta::where('CodigoDocumentoVenta', $codigoVenta)->first();

            if (!$pagoDocVenta) {
                return response()->json(['error' => 'Pago Documento Venta no encontrado.'], 404);
            }

            $pagoDocVenta->Vigente = 0;
            $pagoDocVenta->save();


            if ($anularPago == 1) {

                $pago = Pago::find($pagoDocVenta->CodigoPago);

                if (!$pago) {
                    return response()->json(['error' => 'Pago no encontrado.'], 404);
                }
                $pago->Vigente = 0;
                $pago->save();
            }

            DB::commit();

            return response()->json(['message' => 'Venta anulada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function serieCanje(Request $request)
    {
        $Venta = $request->input('Venta');
        $TipoDocumento = $request->input('TipoDocumento');
        $Sede = $request->input('Sede');

        try {
            $tipoProducto = DB::table('documentoventa as dv')
                ->join('detalledocumentoventa as ddv', 'ddv.CodigoVenta', '=', 'dv.Codigo')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->where('dv.Codigo', $Venta)
                ->selectRaw("CASE WHEN COUNT(DISTINCT p.Tipo) = 1 THEN MAX(p.Tipo) ELSE 'T' END AS TipoProducto")
                ->first(); // Esto devuelve un objeto stdClass con la propiedad TipoProducto

            if ($tipoProducto) {
                // Verificamos que la propiedad TipoProducto no sea null
                $tipoProductoValue = $tipoProducto->TipoProducto;

                // Ahora podemos usar $tipoProductoValue en nuestra siguiente consulta
                $serie = DB::table('localdocumentoventa as ldv')
                    ->where('ldv.CodigoTipoDocumentoVenta', $TipoDocumento)
                    ->where('ldv.CodigoSede', $Sede)
                    ->where('ldv.Vigente', 1)
                    ->where('ldv.TipoProducto', $tipoProductoValue) // Usamos el valor de TipoProducto como cadena
                    ->select('ldv.Codigo', 'ldv.Serie')
                    ->get();

                if (!$serie) {
                    return response()->json(['error' => 'No se encontraron Series para este Documento de Venta'], 200);
                }
            } else {
                // Si TipoProducto es null o no se encuentra, manejar el caso
                return response()->json(['error' => 'No se encontraron Series para este Documento de Venta'], 200);
            }

            return response()->json($serie);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    //revisar el canjear
    public function canjearDocumentoVenta(Request $request)
    {
        $canjeData = $request->input('Canje');

        $NumSerieDoc = $this->consultaNumDocumentoVenta(new Request([
            'sede' =>  $canjeData['CodigoSede'],
            'tipoDocumento' =>  $canjeData['CodigoTipoDocumentoVenta']
        ]));

        $data = $NumSerieDoc->getData();

        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');
        DB::beginTransaction();
        try {
            // 1. Obtener el registro original
            $venta = DB::table('documentoventa')
                ->where('Codigo', $canjeData['CodigoVenta'])
                ->where('CodigoTipoDocumentoVenta', 3)
                ->first();

            if ($venta) {
                // 2. Insertar en documentoventa con los valores obtenidos
                $nuevoCodigoDocumentoVenta = DB::table('documentoventa')->insertGetId([
                    'CodigoDocumentoReferencia' => $venta->Codigo,
                    'CodigoTipoDocumentoVenta' => $canjeData['CodigoTipoDocumentoVenta'], // variableTipoDocumentoVenta
                    'CodigoSede' => $venta->CodigoSede,
                    'Serie' =>  $data->Serie, // variableSerie
                    'Numero' => $data->Numero, // variableNumero
                    'Fecha' => $fecha, // variableFecha
                    'CodigoTrabajador' => $canjeData['CodigoTrabajador'], // variableCodigoTrabajador
                    'CodigoPersona' => $venta->CodigoPersona,
                    'CodigoClienteEmpresa' => $venta->CodigoClienteEmpresa,
                    'TotalGravado' => $venta->TotalGravado,
                    'TotalExonerado' => $venta->TotalExonerado,
                    'TotalInafecto' => $venta->TotalInafecto,
                    'IGVTotal' => $venta->IGVTotal,
                    'MontoTotal' => $venta->MontoTotal,
                    'MontoPagado' => $venta->MontoPagado,
                    'Estado' => $venta->Estado,
                    'EstadoFactura' => $venta->EstadoFactura,
                    'CodigoContratoProducto' => $venta->CodigoContratoProducto,
                    'CodigoCaja' => $canjeData['CodigoCaja'] // variableCaja
                ]);

                // 3. Actualizar el campo Vigente en documentoventa
                DB::table('documentoventa')
                    ->where('Codigo', $canjeData['CodigoVenta'])
                    ->update(['Vigente' => 0]);

                // 4. Actualizar el pagodocumentoventa con el nuevo código generado
                DB::table('pagodocumentoventa')
                    ->where('CodigoDocumentoVenta', $canjeData['CodigoVenta'])
                    ->update(['CodigoDocumentoVenta' => $nuevoCodigoDocumentoVenta]);

                // 5. Actualizar el detallecontrato con el nuevo código generado
                DB::table('detalledocumentoventa')
                    ->where('CodigoVenta', $canjeData['CodigoVenta'])
                    ->update(['CodigoVenta' => $nuevoCodigoDocumentoVenta]);
                DB::commit();
                return response()->json(['message' => 'Documento de venta canjeado correctamente.'], 201);
            } else {
                DB::rollBack();
                return response()->json(['message' => 'No se encontró el documento de venta a canjear.'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Ocurrió un error al canjear documento de venta'], 500);
        }
    }




    public function consultarNotaCredito(Request $request)
    {
        $CodVenta = $request->input('venta');
        $sede = $request->input('sede');

        try {
            $venta = DB::table('documentoventa as dv')
                ->select([
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                        WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                        ELSE 'No identificado'
                    END AS NombreCompleto
                "),
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(td.Siglas, ': ', p.NumeroDocumento)
                        WHEN ce.Codigo IS NOT NULL THEN ce.Ruc
                        ELSE 'Documento no disponible'
                    END AS DocumentoCompleto
                "),
                    DB::raw("COALESCE(CONCAT(paciente.Nombres, ' ', paciente.Apellidos), '') AS NombrePaciente"),
                    DB::raw("COALESCE(CONCAT(tdPaciente.Siglas, ': ', paciente.NumeroDocumento), '') AS DocumentoPaciente"),
                    'tdvREF.Nombre as DocReferencia',
                    'docRef.Serie as SerieReferencia',
                    'docRef.Numero as NumeroReferencia',
                    'tdv.Nombre as TipoDocumentoVenta',
                    'dv.Serie',
                    'dv.Numero',
                    DB::raw('DATE(dv.Fecha) as Fecha'),
                    'cp.Codigo as CodigoContrato',
                    'cp.NumContrato',
                    DB::raw('DATE(cp.Fecha) as FechaContrato'),
                    'NOTACREDITO.Nombre as MotivoNotaCredito'
                ])
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
                ->leftJoin('documentoventa as docRef', 'docRef.Codigo', '=', 'dv.CodigoDocumentoReferencia')
                ->leftJoin('tipodocumentoventa as tdvREF', 'tdvREF.Codigo', '=', 'docRef.CodigoTipoDocumentoVenta')
                ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
                ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
                ->leftJoin('personas as paciente', 'paciente.Codigo', '=', 'dv.CodigoPaciente')
                ->leftJoin('tipo_documentos as tdPaciente', 'tdPaciente.Codigo', '=', 'paciente.CodigoTipoDocumento')
                ->leftJoin('contratoProducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
                ->leftJoin('motivonotacredito as NOTACREDITO', 'NOTACREDITO.Codigo', '=', 'dv.CodigoMotivoNotaCredito')
                ->where('dv.Codigo', '=', $CodVenta)
                ->limit(1)
                ->first();


            $detalle = DB::table('detalledocumentoventa as ddv')
                ->join('sedeproducto as sp', 'sp.CodigoProducto', '=', 'ddv.CodigoProducto')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->where('ddv.CodigoVenta', $CodVenta)
                ->where('sp.CodigoSede', $sede)
                ->select(
                    'ddv.MontoTotal',
                    DB::raw("CASE WHEN p.Tipo = 'B' THEN ddv.Cantidad WHEN p.Tipo = 'S' THEN 1 END AS Cantidad"),
                    'ddv.Descripcion',
                    'ddv.CodigoProducto',
                    'p.Tipo',
                    DB::raw("CASE WHEN tg.Tipo = 'G' THEN ROUND(ddv.MontoTotal - (ddv.MontoTotal / (1 + (tg.Porcentaje / 100))), 2) ELSE 0 END AS MontoIGV"),
                    'tg.Tipo AS TipoGravado',
                    'tg.Codigo AS CodigoTipoGravado'
                )
                ->get();


            $devoluciones = DB::table('devolucionnotacredito as dnc')
                ->join('Egreso as e', 'e.Codigo', '=', 'dnc.Codigo')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'e.CodigoMedioPago')
                ->leftJoin('cuentabancaria as cb', 'cb.Codigo', '=', 'e.CodigoCuentaOrigen')
                ->leftJoin('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria')
                ->where('dnc.CodigoDocumentoVenta', $CodVenta)
                ->select(
                    'e.Monto',
                    'mp.Nombre',
                    DB::raw("CASE 
                        WHEN e.CodigoCuentaOrigen IS NOT NULL 
                        THEN CONCAT(eb.Siglas, '-', cb.Numero) 
                        ELSE NULL 
                    END AS CuentaBancaria"),
                    DB::raw("DATE(e.Fecha) as Fecha")
                )
                ->first();

            // Convertir los valores a números en lugar de cadenas
            $detalle = $detalle->map(function ($item) {
                $item->MontoTotal = (float) $item->MontoTotal;
                $item->MontoIGV = (float) $item->MontoIGV;
                return $item;
            });

            return response()->json(['venta' => $venta, 'detalle' => $detalle, 'devolucion' => $devoluciones], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }











    public function consultarNotaCreditoVenta($CodVenta){

        try{
            $venta = DB::table('documentoventa as dv')
            ->select(
                'dv.Codigo',
                DB::raw('DATE(dv.Fecha) as Fecha'),
                DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                        WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                        ELSE 'No identificado'
                    END AS NombreCompleto
                "),
                DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(td.Siglas, ': ', p.NumeroDocumento)
                        WHEN ce.Codigo IS NOT NULL THEN ce.Ruc
                        ELSE 'Documento no disponible'
                    END AS DocumentoCompleto
                "),
                DB::raw('COALESCE(p.Codigo, 0) AS CodigoPersona'),
                DB::raw('COALESCE(ce.Codigo, 0) AS CodigoEmpresa'),
                DB::raw("
                    CASE
                        WHEN p.Codigo IS NOT NULL THEN p.CodigoTipoDocumento
                        ELSE -1
                    END AS CodTipoDoc
                "),
                'dv.CodigoMedico',
                DB::raw("CONCAT(medico.Nombres, ' ', medico.Apellidos) AS NombreMedico"),
                DB::raw('COALESCE(dv.CodigoPaciente, 0) AS CodigoPaciente'),
                DB::raw("COALESCE(CONCAT(paciente.Nombres, ' ', paciente.Apellidos), '') AS NombrePaciente"),
                DB::raw("COALESCE(CONCAT(tdPaciente.Siglas, ': ', paciente.NumeroDocumento), '') AS DocumentoPaciente"),
                'dv.CodigoTipoDocumentoVenta',
                'tdv.Nombre as TipoDocumentoVenta',
                'dv.Serie',
                'dv.Numero',
                'cp.Codigo as CodigoContrato',
                'cp.NumContrato',
                DB::raw('DATE(cp.Fecha) as FechaContrato')
            )
            ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
            ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
            ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
            ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
            ->leftJoin('personas as paciente', 'paciente.Codigo', '=', 'dv.CodigoPaciente')
            ->leftJoin('personas as medico', 'medico.Codigo', '=', 'dv.CodigoMedico')
            ->leftJoin('tipo_documentos as tdPaciente', 'tdPaciente.Codigo', '=', 'paciente.CodigoTipoDocumento')
            ->leftJoin('contratoProducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
            ->where('dv.Codigo', $CodVenta)
            ->first();

            
            $detalle = DB::table('Producto as P')
            ->joinSub(
                DB::table('DetalleDocumentoVenta as DDNC')
                    ->selectRaw('
                        DDNC.CodigoProducto, 
                        DDNC.Descripcion, 
                        DDNC.Codigo, 
                        SUM(DDNC.Cantidad) - COALESCE(NOTAC.CantidadBoleteada, 0) AS Cantidad, 
                        SUM(DDNC.MontoTotal) + COALESCE(NOTAC.MontoBoleteado, 0) AS Monto
                    ')
                    ->leftJoinSub(
                        DB::table('DocumentoVenta as NC')
                            ->join('DetalleDocumentoVenta as DNC', 'NC.Codigo', '=', 'DNC.CodigoVenta')
                            ->selectRaw('
                                DNC.CodigoProducto, 
                                SUM(DNC.Cantidad) AS CantidadBoleteada, 
                                SUM(DNC.MontoTotal) AS MontoBoleteado
                            ')
                            ->where('NC.CodigoDocumentoReferencia', $CodVenta)
                            ->where('NC.Vigente', 1)
                            ->whereNotNull('NC.CodigoMotivoNotaCredito')
                            ->groupBy('DNC.CodigoProducto'),
                        'NOTAC',
                        'NOTAC.CodigoProducto',
                        '=',
                        'DDNC.CodigoProducto'
                    )
                    ->where('DDNC.CodigoVenta', $CodVenta)
                    ->groupBy('DDNC.CodigoProducto', 'DDNC.Descripcion', 'DDNC.Codigo'),
                'S',
                'P.Codigo',
                '=',
                'S.CodigoProducto'
            )
            ->join('SedeProducto as SP', 'SP.CodigoProducto', '=', 'P.Codigo')
            ->join('TipoGravado as TG', 'TG.Codigo', '=', 'SP.CodigoTipoGravado')
            ->where('S.Monto', '>', 0)
            ->orderBy('S.Descripcion')
            ->selectRaw('
                S.CodigoProducto, 
                S.Descripcion, 
                P.Tipo, 
                CASE WHEN P.Tipo = "B" THEN S.Cantidad ELSE 1 END AS Cantidad, 
                S.Monto as MontoTotal, 
                TG.Tipo AS TipoGravado, 
                TG.Porcentaje AS Porcentaje, 
                TG.Codigo AS CodigoTipoGravado
            ')
            ->get();


            
            return response()->json(['venta' => $venta, 'detalle' => $detalle], 200);

        }catch(\Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    // GENNERAR DATA PDF
    // BOLETA 

    public function boletaVentaPDF($venta)
    {
        try {
            $query = DB::table('documentoventa as dv')
                ->join('detalledocumentoventa as dcv', 'dcv.CodigoVenta', '=', 'dv.Codigo')
                ->join('sedesrec as s', 's.Codigo', '=', 'dv.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->join('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
                ->join('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('pago as pg', 'pg.Codigo', '=', 'pdv.CodigoPago')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'pg.CodigoMedioPago')
                ->join('personas as vendedor', 'vendedor.Codigo', '=', 'dv.CodigoTrabajador')
                ->select(
                    'e.Nombre as empresaNombre',
                    'e.Ruc as ruc',
                    'e.Direccion as direccion',
                    DB::raw("'Lambayeque' AS departamento"),
                    DB::raw("'Chiclayo' AS provincia"),
                    DB::raw("'Chiclayo' AS distrito"),
                    's.Nombre AS sede',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as cliente"),
                    'td.Nombre as documentoIdentidad',
                    'p.NumeroDocumento as numDocumento',
                    'mp.Nombre as FormaPago',
                    'p.Direccion as clienteDireccion',
                    'dv.Fecha as fechaEmision',
                    DB::raw("'Soles' as moneda"),
                    'dv.MontoTotal as totalPagar',
                    'dv.IGVTotal as igv',
                    'dv.TotalGravado as opGravadas',
                    DB::raw("CONCAT(vendedor.Nombres, ' ', vendedor.Apellidos) as vendedor"),
                )
                ->where('dv.Codigo', $venta)
                ->first();

            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->select([
                    'ddv.Cantidad as cantidad',
                    DB::raw("'unidad' AS unidad"),
                    'ddv.Descripcion as descripcion',
                    DB::raw("(ddv.MontoTotal / ddv.Cantidad) as precioUnitario"),
                    DB::raw("0 as descuento"),
                    'ddv.MontoTotal as total'
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();

            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
