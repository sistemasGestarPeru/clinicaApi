<?php

namespace App\Http\Controllers\API\Venta;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\Anulacion;
use App\Models\Recaudacion\DetalleVenta;
use App\Models\Recaudacion\IngresoDinero;
use App\Models\Recaudacion\LocalDocumentoVenta;
use App\Models\Recaudacion\LocalMedioPago;
use App\Models\Recaudacion\Pago;
use App\Models\Recaudacion\PagoDocumentoVenta;
use App\Models\Recaudacion\Venta;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

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

        $codigoVenta = $request->input('venta');
        $pagoData = $request->input('pago');

        if (!$codigoVenta) {
            return response()->json(['error' => 'No se ha encontrado el comprobante de pago'], 400);
        }

        if (!$pagoData) {
            return response()->json(['error' => 'No se han enviado los datos del pago'], 400);
        }

        if (!empty($pagoData)) {
            if (isset($pagoData['CodigoCuentaBancaria']) && $pagoData['CodigoCuentaBancaria'] == 0) {
                $pagoData['CodigoCuentaBancaria'] = null;
            }

            if ($pagoData['CodigoMedioPago'] == 1) {

                $pagoData['Fecha'] = $fecha;
            }
        }

        $ventaData['Fecha'] = $fecha;

        $ventaData['EstadoFactura'] = 'M';

        DB::beginTransaction();

        try {

            $pago = Pago::create($pagoData);
            $codigoPago = $pago->Codigo;

            PagoDocumentoVenta::create([
                'CodigoPago' => $codigoPago,
                'CodigoDocumentoVenta' => $codigoVenta,
                'Monto' => $pagoData['Monto'],
            ]);

            DB::table('documentoventa')
                ->where('Codigo', $codigoVenta)
                ->increment('MontoPagado', $pagoData['Monto']);

            DB::commit();
            return response()->json(['message' => 'Pago registrada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se puedo registrar el pago.'], 500);
        }
    }

    
    public function registrarVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesVentaData = $request->input('detalleVenta');
        $ventaData = $request->input('venta');
        $pagoData = $request->input('pago');
        $detraccion = $request->input('detraccion');

        if (!$detallesVentaData) {
            return response()->json(['error' => 'No se han enviado los detalles de la venta'], 400);
        }

        if (!$ventaData) {
            return response()->json(['error' => 'No se han enviado los datos de la venta'], 400);
        }

        // if (!$pagoData) {
        //     return response()->json(['error' => 'No se han enviado los datos del pago'], 400);
        // }

        if (isset($ventaData['CodigoPersona']) && $ventaData['CodigoPersona'] == 0) {
            $ventaData['CodigoPersona'] = null;
        }

        if (isset($ventaData['CodigoClienteEmpresa']) && $ventaData['CodigoClienteEmpresa'] == 0) {
            $ventaData['CodigoClienteEmpresa'] = null;
        }

        if (isset($ventaData['CodigoContratoProducto']) && $ventaData['CodigoContratoProducto'] == 0) {
            $ventaData['CodigoContratoProducto'] = null;
        }

        if (!empty($pagoData)) {
            if (isset($pagoData['CodigoCuentaBancaria']) && $pagoData['CodigoCuentaBancaria'] == 0) {
                $pagoData['CodigoCuentaBancaria'] = null;
            }

            if ($pagoData['CodigoMedioPago'] == 1) {

                $pagoData['Fecha'] = $fecha;
            }
        }

        $ventaData['Fecha'] = $fecha;

        $ventaData['EstadoFactura'] = 'M';

        // $MedioPago = $pagoData['CodigoMedioPago'];

        // $CodigoTipoDocumentoVenta = $ventaData['CodigoTipoDocumentoVenta'];
        // $sede = $ventaData['CodigoSede'];


        DB::beginTransaction();
        try {

            $venta = Venta::create($ventaData);
            $codigoVenta = $venta->Codigo;

            foreach ($detallesVentaData as $detalle) {
                $detalle['CodigoVenta'] = $codigoVenta;
                DetalleVenta::create($detalle);
            }


            if (!empty($pagoData)) {
                $pago = Pago::create($pagoData);
                $codigoPago = $pago->Codigo;

                PagoDocumentoVenta::create([
                    'CodigoPago' => $codigoPago,
                    'CodigoDocumentoVenta' => $codigoVenta,
                    'Monto' => $pagoData['Monto'],
                ]);
            }

            if (!empty($detraccion)) {
                $detraccion['Fecha'] = $fecha;;
                Pago::create($detraccion);
            }

            if ($ventaData['CodigoContratoProducto']) {
                DB::table('contratoproducto')
                    ->where('Codigo', $ventaData['CodigoContratoProducto'])
                    ->increment('TotalPagado', $pagoData['Monto']);
            }

            //$url = $this->generarPDF(); //asignar a una variable de la tabla DetalleVenta

            DB::commit();
            return response()->json(['message' => 'Venta registrada correctamente.', 'venta' => $venta->Codigo], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage(), 'message' => 'No se puedo registrar la Venta.'], 500);
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

            //Consultar si tiene ventas realizadas

            $detalle = DB::table('detallecontrato as dc')
                    ->selectRaw("
                        (dc.MontoTotal - COALESCE(SUM(ddv.MontoTotal), 0)) AS MontoTotal,
                        CASE
                            WHEN p.Tipo = 'B' THEN (dc.Cantidad - COALESCE(SUM(ddv.Cantidad), 0)) 
                            WHEN p.Tipo = 'S' THEN 1
                        END AS Cantidad,
                        dc.Descripcion,
                        dc.CodigoProducto,
                        p.TipoGravado,
                        p.Tipo,
                        CASE
                            WHEN p.TipoGravado = 'A' THEN ROUND(dc.MontoTotal - (dc.MontoTotal / (1 + 0.18)), 2)
                            ELSE 0
                        END AS MontoIGV
                    ")
                    ->leftJoin('detalledocumentoventa as ddv', function($join) use ($idContrato) {
                        $join->on('dc.CodigoProducto', '=', 'ddv.CodigoProducto')
                            ->whereIn('ddv.CodigoVenta', function($query) use ($idContrato) {
                                $query->select('Codigo')
                                    ->from('documentoventa')
                                    ->where('CodigoContratoProducto', $idContrato);
                            });
                    })
                    ->join('producto as p', 'p.Codigo', '=', 'dc.CodigoProducto')
                    ->where('dc.CodigoContrato', $idContrato)
                    ->groupBy('dc.Codigo', 'dc.Descripcion', 'dc.Cantidad', 'dc.MontoTotal', 'p.Codigo', 'p.TipoGravado', 'p.Tipo')
                    ->havingRaw("
                        CASE
                            WHEN p.Tipo = 'B' THEN (dc.Cantidad - COALESCE(SUM(ddv.Cantidad), 0)) > 0
                            WHEN p.Tipo = 'S' THEN (dc.MontoTotal - COALESCE(SUM(ddv.MontoTotal), 0)) > 0
                            ELSE 0
                        END
                    ")
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



    public function consultarDatosVenta(Request $request)
    {
        $idVenta = $request->input('idVenta');
        try {
            $documentoVenta = DB::table('documentoventa as dv')
                ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
                ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
                ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->where('dv.Codigo', $idVenta)
                ->select(
                    'dv.Codigo',
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, ' ', p.Apellidos)
                        WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                        ELSE 'No identificado'
                    END as NombreCompleto
                "),
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(td.Siglas, ': ', p.NumeroDocumento)
                        WHEN ce.Codigo IS NOT NULL THEN ce.Ruc
                        ELSE 'Documento no disponible'
                    END as DocumentoCompleto
                "),
                    DB::raw('COALESCE(p.Codigo, 0) as CodigoPaciente'),
                    DB::raw('COALESCE(ce.Codigo, 0) as CodigoEmpresa'),
                    DB::raw("
                    CASE
                        WHEN p.Codigo IS NOT NULL THEN p.CodigoTipoDocumento
                        ELSE -1
                    END as CodTipoDoc
                ")
                )
                ->first();


            $detalle = DB::table('detalledocumentoventa as ddc')
                ->join('producto as p', 'p.Codigo', '=', 'ddc.CodigoProducto')
                ->where('ddc.CodigoVenta', $idVenta)
                ->select(
                    'ddc.MontoTotal',
                    'ddc.Cantidad',
                    'ddc.Descripcion',
                    'ddc.CodigoProducto',
                    'p.TipoGravado',
                    DB::raw("
                    CASE 
                        WHEN p.TipoGravado = 'A' THEN ROUND(ddc.MontoTotal - (ddc.MontoTotal / (1 + 0.18)), 2)
                        ELSE 0
                    END as MontoIGV")
                )
                ->get();

            $estadoPago = DB::table('documentoventa')
                ->select(DB::raw("
                    CASE 
                        WHEN SUM(CASE WHEN Vigente = 1 THEN MontoPagado ELSE 0 END) IS NULL THEN MontoTotal
                        WHEN SUM(CASE WHEN Vigente = 1 THEN MontoPagado ELSE 0 END) = MontoTotal THEN 0
                        ELSE MontoTotal - SUM(CASE WHEN Vigente = 1 THEN MontoPagado ELSE 0 END)
                    END AS EstadoPago
                "))
                ->where('Codigo', $idVenta)
                ->groupBy('MontoTotal') // Añadir GROUP BY
                ->first();

            // Convertir los valores a números en lugar de cadenas
            $detalle = $detalle->map(function ($item) {
                $item->MontoTotal = (float) $item->MontoTotal;
                $item->MontoIGV = (float) $item->MontoIGV;
                return $item;
            });

            return response()->json(['venta' => $documentoVenta, 'detalle' => $detalle, 'estadoPago' => $estadoPago], 200);
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


    public function buscarVenta(Request $request)
    {
        try {
            $fecha = $request->input('fecha');
            $codigoSede = $request->input('codigoSede');

            $venta = DB::table('clinica_db.documentoventa')
                ->where(DB::raw("DATE(Fecha)"), $fecha)
                ->where('CodigoSede', $codigoSede)
                ->where('Vigente', 1)
                ->select(
                    'Codigo',
                    'Serie',
                    'Numero',
                    'CodigoTipoDocumentoVenta as TipoDoc',
                    DB::raw("DATE(Fecha) as Fecha"),
                    DB::raw("CASE 
                            WHEN MontoTotal = MontoPagado THEN 0
                            WHEN MontoTotal != MontoPagado THEN 1
                        END AS PagoCompleto")
                )
                ->get();

            return response()->json($venta);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'msg' => 'Error al buscar Venta'], 500);
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

    public function consultarVenta(Request $request)
    {
        $codigo = $request->input('codigoVenta');
        try {
            $resultados = DB::table('documentoventa as dv')
                ->leftJoin('personas as p', 'dv.CodigoPersona', '=', 'p.Codigo')
                ->leftJoin('clienteempresa as ce', 'dv.CodigoClienteEmpresa', '=', 'ce.Codigo')
                ->where('dv.Codigo', $codigo)
                ->where('dv.Vigente', 1)
                ->select(
                    'dv.Codigo',
                    'dv.Numero',
                    DB::raw('CAST(dv.Fecha AS DATE) as fechaVenta'),
                    DB::raw('CASE 
                    WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, \' \', p.Apellidos)
                    WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                    ELSE \'No disponible\'
                END AS nombreCliente'),
                    'dv.MontoPagado as montoPagado',
                )
                ->first();

            return $resultados;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generarPDF(Request $request)
    {
        $codigoVenta = $request->input('codigoVenta');
        $codigoSede = $request->input('codigoSede');

        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $documentoVenta = DB::table('documentoventa as dv')
            ->join('sedesrec as sr', 'sr.Codigo', '=', 'dv.CodigoSede')
            ->join('empresas as e', 'e.Codigo', '=', 'sr.CodigoEmpresa')
            ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
            ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
            ->select(
                'e.Nombre as NombreEmpresa',
                'e.RazonSocial',
                'e.Ruc',
                'e.Direccion as DireccionEmpresa',
                'sr.Nombre as NombreSede',
                'sr.Direccion as DireccionSede',
                'dv.Serie',
                'dv.Numero',
                'dv.MontoTotal',
                'dv.MontoPagado',
                'dv.IGVTotal',
                'dv.TotalExonerado',
                'dv.TotalGravado',
                'dv.TotalInafecto',
                DB::raw('CASE 
                WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial 
                WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Nombres, " ", p.Apellidos) 
                ELSE "Desconocido" 
            END as NombresCliente'),
                DB::raw('CASE 
                WHEN ce.Codigo IS NOT NULL THEN ce.RUC 
                WHEN p.Codigo IS NOT NULL THEN p.NumeroDocumento 
            END as NumDocumento')
            )
            ->where('sr.Codigo', $codigoSede)
            ->where('dv.Codigo', $codigoVenta)
            ->first();  // Usamos first() para obtener un solo registro, o get() si se esperan múltiples

        // Para ver el resultado


        $detalleDocumentoVenta = DB::table('detalledocumentoventa')
            ->select('Numero', 'Descripcion', 'Cantidad', 'MontoTotal')
            ->where('CodigoVenta', $codigoVenta)
            ->get();  // Usamos get() ya que puede haber múltiples registros

        // Para ver el resultado
        return response()->json(['documentoVenta' => $documentoVenta, 'detalleDocumentoVenta' => $detalleDocumentoVenta, 'fecha' => $fecha], 200);
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
}
