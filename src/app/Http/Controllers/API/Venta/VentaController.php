<?php

namespace App\Http\Controllers\API\Venta;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Http\Requests\Recaudacion\Pago\RegistrarPagoRequest;
use App\Http\Requests\Venta\RegistrarDetalleVentaRequest;
use App\Http\Requests\Venta\RegistrarVentaRequest;
use App\Models\Recaudacion\Anulacion;
use App\Models\Recaudacion\DetalleVenta;
use App\Models\Recaudacion\Detraccion;
use App\Models\Recaudacion\DevolucionNotaCredito;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\Pago;
use App\Models\Recaudacion\PagoDocumentoVenta;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use App\Models\Recaudacion\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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

    public function cuentaDetraccion($empresa)
    {
        try {
            $resultados = DB::table('cuentabancaria as cb')
                ->join('entidadbancaria as eb', 'cb.CodigoEntidadBancaria', '=', 'eb.Codigo')
                ->join('empresas as e', 'e.Codigo', '=', 'cb.CodigoEmpresa')
                ->select('cb.Codigo', 'eb.Nombre', 'eb.Siglas', 'cb.Numero', 'cb.CCI', 'e.PorcentajeDetraccion')
                ->where('cb.CodigoEmpresa', $empresa)
                ->where('cb.Detraccion', 1)
                ->where('e.Vigente', 1)
                ->where('cb.Vigente', 1)
                ->where('eb.Vigente', 1)
                ->first();
            return response()->json($resultados, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function anularPago($venta, $pago)
    {
        DB::beginTransaction();
        try {

            $pagoData = DB::table('pago')
                ->where('Codigo', $pago)
                ->select('Vigente', 'CodigoCaja')
                ->first();

            if (!$pagoData) {
                return response()->json(['error' => 'Pago no encontrado.'], 404);
            }

            if ($pagoData->Vigente == 0) {
                return response()->json(['error' => 'El pago ya fue anulado.'], 400);
            }

            $estadoCaja = ValidarFecha::obtenerFechaCaja($pagoData->CodigoCaja);

            if ($estadoCaja->Estado == 'C') {
                return response()->json([
                    'error' => __('mensajes.error_anulacion_pago_caja')
                ], 400);
            }

            $monto = DB::table('pagodocumentoventa')
                ->where('CodigoPago', $pago)
                ->where('CodigoDocumentoVenta', $venta)
                ->where('Vigente', 1)
                ->value('Monto');

            DB::table('Pago')
                ->where('Codigo', $pago)
                ->update(['Vigente' => 0]);

            DB::table('pagodocumentoventa')
                ->where('CodigoPago', $pago)
                ->where('CodigoDocumentoVenta', $venta)
                ->update(['Vigente' => 0]);

            DB::table('documentoventa')
                ->where('Codigo', $venta)
                ->decrement('MontoPagado', $monto);

            DB::commit();
            return response()->json(['message' => 'Pago anulada correctamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al anular el Pago.', 'bd'  =>  $e->getMessage()], 500);
        }
    }
    public function listarPagosAsociados($venta)
    {

        try {

            $pago = DB::table('pago as p')
                ->join('pagodocumentoventa as pdv', 'p.Codigo', '=', 'pdv.CodigoPago')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'p.CodigoMedioPago')
                ->where('pdv.CodigoDocumentoVenta', $venta)
                // ->where('pdv.Vigente', 1)
                // ->where('p.Vigente', 1)
                ->select('p.Codigo', 'mp.Nombre', 'p.Monto', 'p.Fecha', 'mp.CodigoSUNAT', 'p.Vigente')
                ->get();

            return response()->json($pago, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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

        if (isset($pagoData['CodigoCuentaBancaria']) && $pagoData['CodigoCuentaBancaria'] == 0) {
            $pagoData['CodigoCuentaBancaria'] = null;
        }

        if (isset($pagoData['CodigoBilleteraDigital']) && $pagoData['CodigoBilleteraDigital'] == 0) {
            $pagoData['CodigoBilleteraDigital'] = null;
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($pagoData['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($pagoData['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            return response()->json([
                'error' => __('mensajes.error_fecha_recaudacion'),
            ], 400);
        }

        if ($pagoData['CodigoSUNAT'] == '008') {
            $pagoData['CodigoCuentaBancaria'] = null;
            $pagoData['CodigoBilleteraDigital'] = null;
            $pagoData['Lote'] = null;
            $pagoData['Referencia'] = null;
            $pagoData['NumeroOperacion'] = null;
        } else if ($pagoData['CodigoSUNAT'] == '003') {
            $pagoData['Lote'] = null;
            $pagoData['Referencia'] = null;
        } else if ($pagoData['CodigoSUNAT'] == '005' || $pagoData['CodigoSUNAT'] == '006') {
            $pagoData['CodigoCuentaBancaria'] = null;
            $pagoData['CodigoBilleteraDigital'] = null;
        }


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
            return response()->json(['error' => 'No se puedo registrar el pago.', 'db' => $e->getMessage()], 500);
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

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($ventaData['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($ventaData['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura de caja.'], 400);
        }

        if (isset($ventaData['CodigoClienteEmpresa']) && $ventaData['CodigoClienteEmpresa'] == 0) {
            $ventaData['CodigoClienteEmpresa'] = null;
        }

        if (!empty($dataEgreso) && is_array($dataEgreso)) {
            //Validar Egreso
            if (isset($dataEgreso['CodigoCuentaOrigen']) && $dataEgreso['CodigoCuentaOrigen'] == 0) {
                $dataEgreso['CodigoCuentaOrigen'] = null;
            }

            if (isset($dataEgreso['CodigoBilleteraDigital']) && $dataEgreso['CodigoBilleteraDigital'] == 0) {
                $dataEgreso['CodigoBilleteraDigital'] = null;
            }

            if ($dataEgreso['CodigoSUNAT'] == '008') {
                $dataEgreso['CodigoCuentaOrigen'] = null;
                $dataEgreso['CodigoBilleteraDigital'] = null;
                $dataEgreso['Lote'] = null;
                $dataEgreso['Referencia'] = null;
                $dataEgreso['NumeroOperacion'] = null;

                $total = MontoCaja::obtenerTotalCaja($ventaData['CodigoCaja']);

                if ($dataEgreso['Monto'] > $total) {
                    return response()->json(['error' => 'No hay suficiente Efectivo en caja', 'Disponible' => $total], 500);
                }
            } else if ($dataEgreso['CodigoSUNAT'] == '003') {
                $dataEgreso['Lote'] = null;
                $dataEgreso['Referencia'] = null;
            } else if ($dataEgreso['CodigoSUNAT'] == '005' || $dataEgreso['CodigoSUNAT'] == '006') {
                $dataEgreso['CodigoCuentaBancaria'] = null;
                $dataEgreso['CodigoBilleteraDigital'] = null;
            }

            $dataEgreso['CodigoCaja'] = $ventaData['CodigoCaja'];
            $dataEgreso['CodigoTrabajador'] = $ventaData['CodigoTrabajador'];

            $fechaPagoVal = Carbon::parse($dataEgreso['Fecha'])->toDateString(); // Convertir el string a Carbon
            if ($fechaCajaVal < $fechaPagoVal) {
                return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
            }

            //Validar Egreso
            $egresoValidator = Validator::make($dataEgreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();
        }


        DB::beginTransaction();

        $ventaData['TotalGravado'] = $ventaData['TotalGravado'] * -1;
        $ventaData['TotalExonerado'] = $ventaData['TotalExonerado'] * -1;
        $ventaData['TotalInafecto'] = $ventaData['TotalInafecto'] * -1;
        $ventaData['TotalGratis'] = $ventaData['TotalGratis'] * -1;
        $ventaData['IGVTotal'] = $ventaData['IGVTotal'] * -1;
        $ventaData['MontoTotal'] = $ventaData['MontoTotal'] * -1;
        $ventaData['MontoPagado'] = $ventaData['MontoPagado'] * -1;


        try {
            $ventaCreada = Venta::create($ventaData);

            $ventaData['TotalDescuento'] = 0;
            foreach ($detallesVentaData as $detalle) {
                $detalle['Cantidad'] = $detalle['Cantidad'] * -1;
                $detalle['MontoTotal'] = $detalle['MontoTotal'] * -1;
                $detalle['MontoIGV'] = $detalle['MontoIGV'] * -1;
                if (!isset($detalle['Descuento'])) {
                    $detalle['Descuento'] = 0;
                }
                $detalle['Descuento'] = $detalle['Descuento'] * -1;
                $ventaData['TotalDescuento'] += $detalle['Descuento'] * $detalle['Cantidad'];

                $detalle['CodigoVenta'] = $ventaCreada->Codigo;
                DetalleVenta::create($detalle);
            }

            if (!empty($dataEgreso) && is_array($dataEgreso)) {

                $egresoCreado = Egreso::create($dataEgreso);

                $dataDevolucion = [
                    'Codigo' => $egresoCreado->Codigo,
                    'CodigoDocumentoVenta' => $ventaCreada->Codigo,
                ];

                DevolucionNotaCredito::create($dataDevolucion);
            }

            DB::commit();

            // Generar JSON para facturación electrónica con los datos que ya tenemos
            $data = $this->detallesFacturacionElectronica($ventaData, $detallesVentaData, $ventaCreada->Codigo);

            return response()->json([
                'message' => 'Nota de Crédito registrada correctamente.',
                'facturacion' => $data
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registrar Nota de Crédito', 'db' => $e->getMessage()], 500);
        }
    }



    public function detallesFacturacionElectronica($ventaData, $detallesVenta, $ventaCreada)
    {
        try {

            // Obtener datos del emisor 
            $datosEmisor = DB::table('sedesrec as s')
                ->join('empresas as e', 's.CodigoEmpresa', '=', 'e.Codigo')
                ->where('s.Codigo', $ventaData['CodigoSede'])
                ->select([
                    'e.Direccion as txt_dmcl_fisc_emis',
                    'e.RUC as num_ruc_emis',
                    'e.RazonSocial as nom_rzn_soc_emis',
                    's.Codigo as cod_loc_emis',
                    DB::raw('6 as cod_tip_nif_emis'),
                    'e.Departamento as txt_dpto_emis',
                    'e.Provincia as txt_prov_emis',
                    'e.Distrito as txt_distr_emis',
                    'e.CodigoUbigeo as cod_ubi_emis',
                    'e.IDPSE as cod_cliente_emis',
                    'e.TokenPSE as TokenPSE'
                ])
                ->first();
            
            if (!$datosEmisor) {
                return response()->json(['error' => 'Datos del emisor no encontrados.'], 404);
            }

            // Obtener tipo de documento venta
            $tipoDocumentoVenta = DB::table('tipodocumentoventa as tdv')
                ->where('tdv.Codigo', $ventaData['CodigoTipoDocumentoVenta'])
                ->select('tdv.CodigoSUNAT')
                ->first();
    
            // Obtener datos del cliente
            if ($ventaData['CodigoPersona'] != null) {
                $cliente = DB::table('personas as p')
                    ->join('tipo_documentos as td', 'p.CodigoTipoDocumento', '=', 'td.Codigo')
                    ->where('p.Codigo', $ventaData['CodigoPersona'])
                    ->select(
                        'p.NumeroDocumento',
                        DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres"),
                        'td.CodigoSUNAT',
                        'p.Direccion'
                    )
                    ->first();
            } 
            elseif ($ventaData['CodigoClienteEmpresa'] != null) {
                $cliente = DB::table('clienteempresa')
                    ->where('Codigo', $ventaData['CodigoClienteEmpresa'])
                    ->select(
                        'RUC as NumeroDocumento',
                        'RazonSocial as Nombres',
                        DB::raw("6 as CodigoSUNAT"), // Asumiendo que 6 es el código SUNAT para RUC
                        'Direccion'
                    )
                    ->first();
            }


            // Parsear fecha y hora
            $fechaHora = Carbon::parse($ventaData['Fecha']);
            $fechaEmision = $fechaHora->format('Y-m-d');
            $horaEmision = $fechaHora->format('H:i:s');
    
            // Procesar detalles
            $detallesFormateados = [];
            
            foreach ($detallesVenta as $detalle) {

                $datosProductoSede = DB::table('sedeproducto as sp')
                ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                ->join('tipogravado as tg', 'sp.CodigoTipoGravado', '=', 'tg.Codigo')
                ->where('p.Codigo', $detalle['CodigoProducto'])
                ->where('sp.CodigoSede', $ventaData['CodigoSede'])
                ->select([
                    'um.CodigoSUNAT as unidadMedida',
                    'tg.CodigoSUNAT as tipoGravado'
                ])
                ->first();

                $detallesFormateados[] = [
                    'num_lin_item' => $detalle['Numero'],
                    'cod_unid_item' => $datosProductoSede->unidadMedida,
                    'cant_unid_item' => $detalle['Cantidad'] ?? 0,
                    'val_vta_item' => round($detalle['MontoTotal'] - $detalle['MontoIGV'], 4) ?? 0,
                    'cod_tip_afect_igv_item' => $datosProductoSede->tipoGravado,
                    'prc_vta_unit_item' => round($detalle['MontoTotal'] / $detalle['Cantidad'], 4) ?? 0,
                    'mnt_dscto_item' => round($detalle['Descuento'], 4) ?? 0,
                    'mnt_igv_item' => round($detalle['MontoIGV'], 4) ?? 0,
                    'txt_descr_item' => $detalle['Descripcion'] ?? 'Producto sin descripción',
                    'cod_prod_sunat' => $detalle['CodigoSunat'] ?? '00000000', //Ni idea de que es
                    'cod_item' => $detalle['CodigoProducto'] ?? '00000', //Ni idea de que es
                    'val_unit_item' => round(($detalle['MontoTotal'] - $detalle['MontoIGV'])/$detalle['Cantidad'], 4) ?? 0,
                    'importe_total_item' => $detalle['MontoTotal'] ?? 0
                ];
            }

            switch ($tipoDocumentoVenta->CodigoSUNAT) {
                case '01':
                    $identificador = 'FC'; // Factura
                    break;
                case '03':
                    $identificador = 'BC'; // Boleta de venta
                    break;
                case '07':
                    $identificador = 'BC'; // Nota de crédito cambiar
                    break;
                case '08':
                    $identificador = 'BC'; // Nota de debito cambiar
                    break;

                default:
                    return response()->json(['error' => 'Tipo de comprobante no válido.'], 400);
            }

            // Construir el JSON final
            $facturacionElectronica = [
                //Detalle Emisor
                'identificador' => $identificador, // Tipo de documento BC: Boleta de Venta, FC: Factura 
                'fec_emis' => $fechaEmision,
                'hora_emis' => $horaEmision,
                'txt_serie' => $ventaData['Serie'] ?? '',
                'txt_correlativo' => $ventaData['Numero'] ?? '',
                'cod_tip_cpe' =>  $tipoDocumentoVenta->CodigoSUNAT, //Tipo de comprobante 01 Factura y 03 Boleta
                'cod_mnd'=> 'PEN', //Moneda en Duracel por el momento
                'cod_cliente_emis' => $datosEmisor->cod_cliente_emis,
                'num_ruc_emis'=> $datosEmisor->num_ruc_emis,
                'nom_rzn_soc_emis' => $datosEmisor->nom_rzn_soc_emis,
                'cod_tip_nif_emis' => $datosEmisor->cod_tip_nif_emis, 
                'cod_loc_emis' => 1, // NI IDEA QUE SIGNIFICA
                'cod_ubi_emis' => $datosEmisor->cod_ubi_emis,
                'txt_dmcl_fisc_emis' => $datosEmisor->txt_dmcl_fisc_emis,
                'txt_prov_emis' => $datosEmisor->txt_prov_emis,
                'txt_dpto_emis' => $datosEmisor->txt_dpto_emis,
                'txt_distr_emis' => $datosEmisor->txt_distr_emis,

                //Detalles del cliente / receptor

                'num_iden_recp' => $cliente->NumeroDocumento ?? null,
                'cod_tip_nif_recp' => $cliente->CodigoSUNAT ?? null,
                'nom_rzn_soc_recp' => $cliente->Nombres ?? null,
                'txt_dmcl_fisc_recep'=> $cliente->Direccion ?? null,

                // 'txt_correo_adquiriente'
                
                //Detalle de la venta
                'mnt_tot_gravadas'=> $ventaData['TotalGravado'] ?? 0.00,
                'mnt_tot_inafectas'=> $ventaData['TotalInafecto'] ?? 0.00,
                'mnt_tot_exoneradas'=> $ventaData['TotalExonerado'] ?? 0.00,
                'mnt_tot_gratuitas'=> $ventaData['TotalGratis'] ?? 0.00,
                'mnt_tot_desc_global'=> $ventaData['TotalDescuento'], 
                'mnt_tot_igv'=> $ventaData['IGVTotal'] ?? 0.00,
                'mnt_tot' => $ventaData['MontoTotal'] ?? 0.00,
                
                'detalles' => $detallesFormateados
            ];

            $data = [
                'facturacion' => $facturacionElectronica,
                'token' => $datosEmisor->TokenPSE,
                'venta' => $ventaCreada
            ];
            return $data;
    
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(['error' => 'Error al generar el JSON de facturación electrónica: ' . $e->getMessage()], 500);
        }
    }


    public function registrarVenta(Request $request)
    {
        $ventaData = $request->input('venta');
        $detallesVentaData = $request->input('detalleVenta');
        $pagoData = $request->input('pago');
        $detraccion = $request->input('detraccion');
        $temporales = $request->input('temporal');

        $cantidadesPorTemporal = [];

        if (!empty($temporales) && is_array($temporales)) {

            foreach ($temporales as $temp) {
                if (isset($temp['Temporal'], $temp['Cantidad']) && $temp['Temporal'] != 0) {
                    $cantidadesPorTemporal[$temp['Temporal']] =
                        ($cantidadesPorTemporal[$temp['Temporal']] ?? 0) + $temp['Cantidad'];
                }
            }
        }

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

        if (isset($ventaData['CodigoMedico']) && $ventaData['CodigoMedico'] == 0) {
            $ventaData['CodigoMedico'] = null;
        }

        //validar Detalles de Venta
        $detalleVentaValidar = Validator::make(
            ['detalleVenta' => $detallesVentaData],
            (new RegistrarDetalleVentaRequest())->rules()
        );
        $detalleVentaValidar->validate();

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($ventaData['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaVentaVal = Carbon::parse($ventaData['Fecha'])->toDateString(); // Convertir el string a Carbon

        if ($fechaCajaVal < $fechaVentaVal) {
            return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
        }

        //Validar Pago
        if ($pagoData) {
            $pagoData['Monto'] = $ventaData['MontoPagado'];
            $pagoData['CodigoCaja'] = $ventaData['CodigoCaja'];
            $pagoData['CodigoTrabajador'] = $ventaData['CodigoTrabajador'];

            $pagoValidator = Validator::make($pagoData, (new RegistrarPagoRequest())->rules());
            $pagoValidator->validate();

            if (isset($pagoData['CodigoCuentaBancaria']) && $pagoData['CodigoCuentaBancaria'] == 0) {
                $pagoData['CodigoCuentaBancaria'] = null;
            }

            if (isset($pagoData['CodigoBilleteraDigital']) && $pagoData['CodigoBilleteraDigital'] == 0) {
                $pagoData['CodigoBilleteraDigital'] = null;
            }

            if ($pagoData['CodigoSUNAT'] == '008') {
                $pagoData['Fecha'] = $ventaData['Fecha'];
                $pagoData['CodigoCuentaBancaria'] = null;
                $pagoData['CodigoBilleteraDigital'] = null;
                $pagoData['Lote'] = null;
                $pagoData['Referencia'] = null;
                $pagoData['NumeroOperacion'] = null;
            } else if ($pagoData['CodigoSUNAT'] == '003') {
                $pagoData['Lote'] = null;
                $pagoData['Referencia'] = null;
            } else if ($pagoData['CodigoSUNAT'] == '005' || $pagoData['CodigoSUNAT'] == '006') {
                $pagoData['CodigoCuentaBancaria'] = null;
                $pagoData['CodigoBilleteraDigital'] = null;
            }
            $fechaPagoVal = Carbon::parse($pagoData['Fecha'])->toDateString(); // Convertir el string a Carbon
            if ($fechaCajaVal < $fechaPagoVal) {
                return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
            }
        }


        DB::beginTransaction();
        try {

            $ventaCreada = Venta::create($ventaData);

            $ventaData['TotalDescuento'] = 0;

            foreach ($detallesVentaData as $detalle) {
                $detalle['CodigoVenta'] = $ventaCreada->Codigo;
                if (!isset($detalle['Descuento'])) {
                    $detalle['Descuento'] = 0;
                }
                // $detalle['MontoTotal'] = $detalle['MontoTotal'] + ($detalle['Descuento'] * $detalle['Cantidad']);
                $ventaData['TotalDescuento'] += $detalle['Descuento'] * $detalle['Cantidad'];
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

            if ($ventaData['MontoTotal'] >= 700 && !empty((array) $detraccion)) {
                $detraccion['CodigoDocumentoVenta'] = $ventaCreada->Codigo;
                Detraccion::create($detraccion);
            }

            if (!empty($cantidadesPorTemporal)) {
                foreach ($cantidadesPorTemporal as $temporal => $cantidadReducir) {
                    DB::table('preciotemporal')
                        ->where('Codigo', $temporal)
                        ->where('Stock', '>=', $cantidadReducir) // Evita stocks negativos
                        ->decrement('Stock', $cantidadReducir);
                }
            }

            DB::commit();

            // Generar JSON para facturación electrónica con los datos que ya tenemos
            $data = $this->detallesFacturacionElectronica($ventaData, $detallesVentaData, $ventaCreada->Codigo);

            return response()->json([
                'message' => 'Venta registrada correctamente.',
                'facturacion' => $data
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registrar la Venta.', 'db' => $e->getMessage()], 500);
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
                    'tg.Porcentaje',
                    'ddv.Descuento'
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


            $detalle = DB::table('producto as P')
                ->joinSub(
                    DB::table('detallecontrato as DC')
                        ->leftJoinSub(
                            DB::table('documentoventa as DV')
                                ->join('detalledocumentoventa as DDV', 'DV.Codigo', '=', 'DDV.CodigoVenta')
                                ->where('DV.CodigoContratoProducto', $idContrato)
                                ->where('DV.Vigente', 1)
                                ->groupBy('DDV.CodigoDetalleContrato', 'DDV.CodigoProducto')
                                ->select(
                                    'DDV.CodigoDetalleContrato',
                                    'DDV.CodigoProducto',
                                    DB::raw('SUM(DDV.Cantidad) AS CantidadBoleteada'),
                                    DB::raw('SUM(DDV.MontoTotal) AS MontoBoleteado')
                                ),
                            'Bol',
                            function ($join) {
                                $join->on('Bol.CodigoProducto', '=', 'DC.CodigoProducto')
                                    ->on('Bol.CodigoDetalleContrato', '=', 'DC.Codigo');
                            }
                        )
                        ->where('DC.CodigoContrato', $idContrato)
                        ->groupBy('DC.CodigoProducto', 'DC.Descripcion', 'DC.Codigo', 'DC.Descuento')
                        ->select(
                            'DC.CodigoProducto',
                            'DC.Descripcion',
                            'DC.Codigo',
                            'DC.Descuento',
                            DB::raw('SUM(DC.Cantidad) - COALESCE(SUM(Bol.CantidadBoleteada), 0) AS Cantidad'),
                            DB::raw('SUM(DC.MontoTotal) - COALESCE(SUM(Bol.MontoBoleteado), 0) AS Monto')
                        ),
                    'S',
                    'P.Codigo',
                    '=',
                    'S.CodigoProducto'
                )
                ->join('sedeproducto as SP', 'SP.CodigoProducto', '=', 'P.Codigo')
                ->join('tipogravado as TG', 'TG.Codigo', '=', 'SP.CodigoTipoGravado')
                ->where('S.Monto', '>', 0)
                ->orderBy('S.Descripcion')
                ->select(
                    'S.Codigo AS CodigoDetalleContrato',
                    'S.CodigoProducto',
                    'S.Descripcion',
                    'S.Descuento',
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
        $nombDoc = $request->input('nombDoc');
        $codDocumento = $request->input('codDocumento');

        try {
            if ($nombDoc == 'RUC') {
                $cliente = DB::table('clienteempresa as ce')
                    // ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'ce.CodigoDepartamento')
                    ->where('ce.RUC', $numDocumento)
                    ->where('ce.Vigente', 1)
                    // ->where('s.Codigo', $codSede)
                    ->select('ce.Codigo', 'ce.RazonSocial as NombreCompleto', DB::raw('0 as TipoCliente'))
                    ->first();
            } else {
                $cliente = DB::table('personas as p')
                    // ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'p.CodigoDepartamento')
                    // ->where('s.Codigo', $codSede)
                    // ->where('s.Vigente', 1)
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
                ->join('producto as p', 'p.Codigo', '=', 'sp.CodigoProducto')
                ->join('tipogravado as tg', 'tg.Codigo', '=', 'sp.CodigoTipoGravado')
                ->select(
                    'p.Codigo',
                    'p.Nombre',
                    'sp.Precio as Monto',
                    'p.Tipo',
                    'tg.Tipo as TipoGravado',
                    'tg.Codigo as CodigoTipoGravado',
                    'tg.Porcentaje',
                    'tg.CodigoSUNAT',
                    'sp.Stock',
                    'sp.Controlado'
                )
                ->where('sp.CodigoSede', $sede) // Usar la sede proporcionada en la solicitud
                ->where(function ($query) {
                    $query->where('sp.Stock', '>', 0)
                        ->orWhere('sp.Controlado', 0);
                })
                ->where('sp.Vigente', 1)
                ->where('p.Vigente', 1)
                ->where('tg.Vigente', 1)
                ->where('p.Nombre', 'LIKE', "{$nombreProducto}%")
                ->where(function ($query) use ($tipo) {
                    $query->where('p.Tipo', $tipo)
                        ->orWhereNotExists(function ($subquery) use ($tipo) {
                            $subquery->select(DB::raw(1))
                                ->from('producto')
                                ->where('Tipo', $tipo)
                                ->where('Vigente', 1);
                        })
                        ->orWhere(function ($subquery) use ($tipo) { // <-- Aquí se añade use($tipo)
                            $subquery->where('p.Tipo', 'C')
                                ->whereNotExists(function ($innerQuery) use ($tipo) {
                                    $innerQuery->select(DB::raw(1))
                                        ->from('productocombo as pc')
                                        ->join('producto as p2', 'p2.Codigo', '=', 'pc.CodigoProducto')
                                        ->whereColumn('pc.CodigoCombo', 'p.Codigo')
                                        ->where('p2.Tipo', '!=', $tipo);
                                });
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
            $documento = $request->input('documento');
            $docVenta = $request->input('docVenta');
            $venta = DB::table('documentoventa as dv')
                ->selectRaw("
                dv.Vigente,
                dv.Codigo,
                dv.CodigoTipoDocumentoVenta AS TipoDoc,
                CONCAT(tdv.Siglas, ' ', dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) AS Documento,
                DATE(dv.Fecha) AS Fecha,
                ABS(dv.MontoTotal) as MontoTotal,
                ABS(dv.MontoPagado) as MontoPagado,
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
                tdv.CodigoSUNAT AS CodigoSUNAT,
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
                ->when($docVenta != 0, fn($query) => $query->where('dv.CodigoTipoDocumentoVenta', $docVenta))
                ->when($nombre, function ($query) use ($nombre) {
                    $query->where(function ($q) use ($nombre) {
                        $q->where('p.Nombres', 'LIKE', "%$nombre%")
                            ->orWhere('p.Apellidos', 'LIKE', "%$nombre%")
                            ->orWhere('ce.RazonSocial', 'LIKE', "%$nombre%");
                    });
                })
                ->when($documento, function ($queryD) use ($documento) {
                    $queryD->where(function ($q) use ($documento) {
                        $q->where('p.NumeroDocumento', 'LIKE', "$documento%")
                            ->orWhere('ce.RUC', 'LIKE', "$documento%");
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
        $serie = $request->input('serie');
        try {

            $result = DB::table('localdocumentoventa')
                ->select('Codigo', 'Serie')
                ->whereIn('CodigoSerieDocumentoVenta', function ($query) use ($serie, $sede) {
                    $query->select('Codigo')
                        ->from('localdocumentoventa')
                        ->where('Serie', $serie)
                        ->where('Vigente', 1)
                        ->where('CodigoSede', $sede);
                })
                ->where('Vigente', 1)
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
                    WHEN ldv.TipoProducto = 'B' THEN 'BIEN'
                    WHEN ldv.TipoProducto = 'S' THEN 'SERVICIO'
                    WHEN ldv.TipoProducto = 'T' THEN 'TODO'
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
            $documentoVenta = DB::table('documentoventa')
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


    public function anularFacturacionElectronica($codigoVenta, $anulacionData){

        try{

            //Obtener datos de la venta

            $datosVenta = DB::table('documentoventa as dv')
            ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
            ->where('dv.Codigo', $codigoVenta)
            ->select([
                'dv.Codigo',
                'dv.Fecha',
                'dv.Serie',
                'dv.Numero',
                'tdv.CodigoSUNAT',
                'dv.CodigoSede'
            ])
            ->first();

            // Obtener datos del emisor 
            $datosEmisor = DB::table('sedesrec as s')
            ->join('empresas as e', 's.CodigoEmpresa', '=', 'e.Codigo')
            ->where('s.Codigo', $datosVenta->CodigoSede)
            ->select([
                'e.RUC as num_ruc_emis',
                'e.IDPSE as cod_cliente_emis',
                'e.TokenPSE as TokenPSE'
            ])
            ->first();

            if (!$datosEmisor) {
            return response()->json(['error' => 'Datos del emisor no encontrados.'], 404);
            }
            // Construir el JSON final

            switch ($datosVenta->CodigoSUNAT) {
            case '03':
                $identificador = 'CB'; // Boleta de Venta
                break;
            case '01':
                $identificador = 'FC'; // Factura
                break;
            default:
                return response()->json(['error' => 'Tipo de documento no soportado.'], 400);
            } 

            $anulacionJSON = [
                'identificador' => 'CB', //Para todo incluyendo Bolete Factura etc (creo)
                'cod_tip_cpe' => $datosVenta->CodigoSUNAT,

                'fec_emis' => $datosVenta->Fecha,
                'txt_serie' => $datosVenta->Serie,
                'txt_correlativo' => $datosVenta->Numero,

                'cod_cliente_emis' => $datosEmisor->cod_cliente_emis,
                'num_ruc_emis' => $datosEmisor->num_ruc_emis,

                'cod_iden_cb' => 'C', // Ni idea
                'cod_tip_escenario' => '01', // Algo de Codigo SUNAT CREO
                'fec_gener_baja' => $anulacionData['Fecha'], 
                'txt_descr_mtvo_baja' =>  'ERROR EN EL SISTEMA'//Creo o Relacionado al codigo Sunat o es el CodigoMotivo.

            ];

            $data = [
                'anulacion' => $anulacionJSON,
                'token' => $datosEmisor->TokenPSE
            ];
            return $data;

        }catch(\Exception $e){
            return response()->json(['error' => 'Error al generar el JSON de anulación electrónica.', 'bd'=> $e->getMessage()], 500);
        }

    }

    public function anularVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $anulacionData = $request->input('Anulacion');
        $codigoVenta = $anulacionData['CodigoDocumentoVenta'];
        $anularPago = $anulacionData['Confirmacion'];
        $tipo = $anulacionData['Tipo'];
        $anulacionData['Fecha'] = $fecha;

        if (!$codigoVenta || $codigoVenta == 0) {
            return response()->json(['error' => 'No se ha encontrado la venta a anular.'], 404);
        }

        DB::beginTransaction();
        try {

            $anulacionCreada = Anulacion::create($anulacionData);

            if ($anularPago == 1) {

                // Obtener los códigos de pagos a desactivar
                $pagos = DB::table('pagodocumentoventa as pdv')
                    ->join('pago as pg', 'pg.Codigo', '=', 'pdv.CodigoPago')
                    ->where('pdv.CodigoDocumentoVenta', $codigoVenta)
                    ->where('pg.Vigente', 1)
                    ->where('pdv.Vigente', 1)
                    ->pluck('pdv.CodigoPago');

                DB::table('pagodocumentoventa')
                    ->where('CodigoDocumentoVenta', $codigoVenta)
                    ->update(['Vigente' => 0]);


                // Marcar como no vigentes los pagos encontrados
                DB::table('pago')
                    ->whereIn('Codigo', $pagos)
                    ->update(['Vigente' => 0]);

                // Marcar la venta como no vigente
                DB::table('documentoventa')
                    ->where('Codigo', $codigoVenta)
                    ->update(['Vigente' => 0]);

                //Disminuir el monto pagado en el contrato
                if ($tipo == 'C') {
                    DB::transaction(function () use ($codigoVenta) {
                        $contrato = DB::table('documentoventa')
                            ->where('Codigo', $codigoVenta)
                            ->select('CodigoContratoProducto', 'MontoPagado')
                            ->first();

                        if ($contrato && $contrato->CodigoContratoProducto !== null && is_numeric($contrato->MontoPagado)) {
                            DB::table('contratoproducto')
                                ->where('Codigo', $contrato->CodigoContratoProducto)
                                ->decrement('TotalPagado', $contrato->MontoPagado);
                        }
                    });
                }
            } else {
                if ($anularPago == 0) {
                    // Marcar la venta como no vigente
                    DB::table('documentoventa')
                        ->where('Codigo', $codigoVenta)
                        ->update(['Vigente' => 0]);

                    DB::table('pagodocumentoventa')
                        ->where('CodigoDocumentoVenta', $codigoVenta)
                        ->update(['Vigente' => 0]);
                }
            }

            DB::commit();

            // Generar JSON para anulacion electrónica con los datos que ya tenemos
            $data = $this->anularFacturacionElectronica($codigoVenta, $anulacionData);

            return response()->json(['message' => 'Venta anulada correctamente.', 'anulacion' => $data, 'codigo' => $anulacionCreada->Codigo], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al anular la venta.', 'bd' => $e->getMessage()], 500);
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

        if ($canjeData['CodigoPersona'] == 0) {
            $canjeData['CodigoPersona'] = null;
        }

        if ($canjeData['CodigoClienteEmpresa'] == 0) {
            $canjeData['CodigoClienteEmpresa'] = null;
        }

        // $NumSerieDoc = $this->consultaNumDocumentoVenta(new Request([
        //     'sede' =>  $canjeData['CodigoSede'],
        //     'tipoDocumento' =>  $canjeData['CodigoTipoDocumentoVenta'],
        //     'serie' =>  $canjeData['Serie']
        // ]));

        // $data = $NumSerieDoc->getData();

        DB::beginTransaction();
        try {
            // 1. Obtener el registro original
            $venta = DB::table('documentoventa')
                ->where('Codigo', $canjeData['CodigoDocumentoReferencia'])
                ->where('Vigente', 1)
                ->first();

            $detalleVenta = DB::table('detalledocumentoventa')
                ->where('CodigoVenta', $canjeData['CodigoDocumentoReferencia'])
                ->get();

            if ($venta) {
                // 2. Insertar en documentoventa con los valores obtenidos
                $nuevoCodigoDocumentoVenta = DB::table('documentoventa')->insertGetId([
                    'CodigoSede' => $canjeData['CodigoSede'],
                    'Serie' => $canjeData['Serie'],
                    'Numero' => $canjeData['Numero'],
                    'CodigoTipoDocumentoVenta' => $canjeData['CodigoTipoDocumentoVenta'],
                    'Fecha' =>  $canjeData['Fecha'],
                    'CodigoPersona' => $canjeData['CodigoPersona'],
                    'CodigoClienteEmpresa' => $canjeData['CodigoClienteEmpresa'],
                    'CodigoCaja' => $canjeData['CodigoCaja'],
                    'CodigoTrabajador' => $canjeData['CodigoTrabajador'],
                    'TotalGravado' => $venta->TotalGravado,
                    'TotalExonerado' => $venta->TotalExonerado,
                    'TotalInafecto' => $venta->TotalInafecto,
                    'IGVTotal' => $venta->IGVTotal,
                    'MontoTotal' => $venta->MontoTotal,
                    'MontoPagado' => $venta->MontoPagado,
                    'Estado' => $venta->Estado,
                    'EstadoFactura' => $venta->EstadoFactura,
                    'CodigoContratoProducto' => $venta->CodigoContratoProducto,
                    'CodigoAutorizador' => $venta->CodigoAutorizador,
                    'CodigoMedico' => $venta->CodigoMedico,
                    'CodigoPaciente' => $venta->CodigoPaciente,
                    'TotalGratis' => $venta->TotalGratis,
                ]);

                // Insertar en detalledocumentoventa con los valores obtenidos

                foreach ($detalleVenta as $detalle) {
                    DB::table('detalledocumentoventa')->insert([
                        'Numero' => $detalle->Numero,
                        'Descripcion' => $detalle->Descripcion,
                        'Cantidad' => $detalle->Cantidad,
                        'MontoTotal' => $detalle->MontoTotal,
                        'MontoIGV' => $detalle->MontoIGV,
                        'CodigoVenta' => $nuevoCodigoDocumentoVenta,
                        'CodigoProducto' => $detalle->CodigoProducto,
                        'Descuento' => $detalle->Descuento,
                        'CodigoTipoGravado' => $detalle->CodigoTipoGravado,
                    ]);
                }

                // 3. Actualizar el campo Vigente en documentoventa
                DB::table('documentoventa')
                    ->where('Codigo', $canjeData['CodigoDocumentoReferencia'])
                    ->update(['Vigente' => 0, 'Estado' => 'C', 'CodigoDocumentoReferencia' => $nuevoCodigoDocumentoVenta]);

                // 4. Actualizar el pagodocumentoventa con el nuevo código generado
                DB::table('pagodocumentoventa')
                    ->where('CodigoDocumentoVenta', $canjeData['CodigoDocumentoReferencia'])
                    ->update(['CodigoDocumentoVenta' => $nuevoCodigoDocumentoVenta]);


                // 5. Actualizar el detallecontrato con el nuevo código generado
                // DB::table('detalledocumentoventa')
                //     ->where('CodigoVenta', $canjeData['CodigoDocumentoReferencia'])
                //     ->update(['CodigoVenta' => $nuevoCodigoDocumentoVenta]);
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
                ->join('egreso as e', 'e.Codigo', '=', 'dnc.Codigo')
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


    public function consultarNotaCreditoVenta($CodVenta)
    {

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
                    DB::raw('DATE(cp.Fecha) as FechaContrato'),
                    'dv.MontoTotal',
                    'dv.MontoPagado',
                    // Subconsulta para obtener el pago activo
                    DB::raw("
                    (
                        COALESCE(
                            (SELECT SUM(Monto) 
                            FROM pagodocumentoventa 
                            WHERE CodigoDocumentoVenta = dv.Codigo 
                            AND Vigente = 1), 
                        0) 
                        +
                        COALESCE(
                            (SELECT SUM(MontoTotal) 
                            FROM detalledocumentoventa 
                            WHERE CodigoVenta = (
                                SELECT Codigo 
                                FROM documentoventa 
                                WHERE CodigoDocumentoReferencia = dv.Codigo 
                                LIMIT 1
                            )
                            ), 
                        0)
                    ) AS PagoActivo
                ")

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
                ->limit(1)
                ->first();


            $detalle = DB::table('producto as P')
                ->joinSub(
                    DB::table('detalledocumentoventa as DDNC')
                        ->selectRaw('
                        DDNC.CodigoProducto, 
                        DDNC.Descripcion, 
                        DDNC.Codigo, 
                        DDNC.Descuento,
                        SUM(DDNC.Cantidad) - COALESCE(NOTAC.CantidadBoleteada, 0) AS Cantidad, 
                        SUM(DDNC.MontoTotal) + COALESCE(NOTAC.MontoBoleteado, 0) AS Monto
                    ')
                        ->leftJoinSub(
                            DB::table('documentoventa as NC')
                                ->join('detalledocumentoventa as DNC', 'NC.Codigo', '=', 'DNC.CodigoVenta')
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
                        ->groupBy('DDNC.CodigoProducto', 'DDNC.Descripcion', 'DDNC.Codigo', 'DDNC.Descuento'),
                    'S',
                    'P.Codigo',
                    '=',
                    'S.CodigoProducto'
                )
                ->join('sedeproducto as SP', 'SP.CodigoProducto', '=', 'P.Codigo')
                ->join('tipogravado as TG', 'TG.Codigo', '=', 'SP.CodigoTipoGravado')
                ->where('S.Monto', '>', 0)
                ->orderBy('S.Descripcion')
                ->selectRaw('
                S.CodigoProducto, 
                S.Descripcion, 
                S.Descuento,
                P.Tipo, 
                CASE WHEN P.Tipo = "B" THEN S.Cantidad ELSE 1 END AS Cantidad, 
                S.Monto as MontoTotal, 
                TG.Tipo AS TipoGravado, 
                TG.Porcentaje AS Porcentaje, 
                TG.Codigo AS CodigoTipoGravado
            ')
                ->get();

            return response()->json(['venta' => $venta, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    // GENNERAR DATA PDF
    // BOLETA 

    public function boletaVentaPDF($venta)
    {
        try {
            $query = DB::table('documentoventa as dv')
                ->join('sedesrec as s', 's.Codigo', '=', 'dv.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->join('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
                ->join('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('pago as pg', 'pg.Codigo', '=', 'pdv.CodigoPago')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'pg.CodigoMedioPago')
                ->join('personas as vendedor', 'vendedor.Codigo', '=', 'dv.CodigoTrabajador')
                ->select(
                    'dv.Vigente',
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
                    'td.Siglas as documentoIdentidad',
                    'p.NumeroDocumento as numDocumento',
                    'mp.Nombre as FormaPago',
                    'p.Direccion as clienteDireccion',
                    'dv.Fecha as fechaEmision',
                    DB::raw("'Soles' as moneda"),
                    'dv.MontoTotal as totalPagar',
                    'dv.IGVTotal as igv',
                    'dv.TotalGravado as opGravadas',
                    DB::raw("CONCAT(vendedor.Nombres, ' ', vendedor.Apellidos) as vendedor"),
                    DB::raw("(SELECT SUM(Descuento * Cantidad) FROM detalledocumentoventa WHERE CodigoVenta = dv.Codigo) AS descuentoTotal")
                )
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();


            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->select([
                    'ddv.Cantidad as cantidad',
                    DB::raw("'unidad' AS unidad"),
                    'ddv.Descripcion as descripcion',
                    DB::raw("( (ddv.MontoTotal + ddv.Descuento) / ddv.Cantidad) as precioUnitario"),
                    'ddv.Descuento as descuento',
                    'ddv.MontoTotal as total'
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();

            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function facturaVentaPDF($venta)
    {
        try {
            $query = DB::table('documentoventa as dv')
                ->join('detalledocumentoventa as dcv', 'dcv.CodigoVenta', '=', 'dv.Codigo')
                ->join('sedesrec as s', 's.Codigo', '=', 'dv.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
                ->leftJoin('personas as pEmp', 'pEmp.Codigo', '=', 'dv.CodigoPersona')
                ->leftJoin('pagodocumentoventa as pdv', 'pdv.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('pago as pg', 'pg.Codigo', '=', 'pdv.CodigoPago')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'pg.CodigoMedioPago')
                ->join('personas as vendedor', 'vendedor.Codigo', '=', 'dv.CodigoTrabajador')
                ->select(
                    'dv.Vigente',
                    'e.Nombre as empresaNombre',
                    'e.Ruc as ruc',
                    'e.Direccion as direccion',
                    DB::raw("'Lambayeque' AS departamento"),
                    DB::raw("'Chiclayo' AS provincia"),
                    DB::raw("'Chiclayo' AS distrito"),
                    's.Nombre AS sede',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("CASE
                                WHEN dv.CodigoPersona IS NOT NULL THEN CONCAT(pEmp.Nombres, ' ', pEmp.Apellidos)
                                WHEN dv.CodigoClienteEmpresa IS NOT NULL THEN e.RazonSocial
                                ELSE 'N/A'
                            END AS cliente"),
                    DB::raw("'RUC' AS documentoIdentidad"),
                    DB::raw("CASE
                                WHEN dv.CodigoPersona IS NOT NULL THEN pEmp.NumeroDocumento
                                WHEN dv.CodigoClienteEmpresa IS NOT NULL THEN ce.RUC
                                ELSE 'N/A'
                            END AS numDocumento"),
                    'mp.Nombre as FormaPago',
                    DB::raw("CASE
                                WHEN dv.CodigoPersona IS NOT NULL THEN pEmp.Direccion
                                WHEN dv.CodigoClienteEmpresa IS NOT NULL THEN ce.Direccion
                                ELSE 'N/A'
                            END AS clienteDireccion"),
                    'dv.Fecha as fechaEmision',
                    DB::raw("'Soles' as moneda"),
                    'dv.MontoTotal as totalPagar',
                    'dv.IGVTotal as igv',
                    'dv.TotalGravado as opGravadas',
                    DB::raw("CONCAT(vendedor.Nombres, ' ', vendedor.Apellidos) as vendedor"),
                    DB::raw("(SELECT SUM(Descuento) FROM detalledocumentoventa WHERE CodigoVenta = dv.Codigo) AS descuentoTotal")
                )
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();

            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->select([
                    'ddv.Cantidad as cantidad',
                    DB::raw("'unidad' AS unidad"),
                    'ddv.Descripcion as descripcion',
                    DB::raw("( (ddv.MontoTotal + ddv.Descuento) / ddv.Cantidad) as precioUnitario"),
                    'ddv.Descuento as descuento',
                    'ddv.MontoTotal as total'
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();
            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function notaCreditoPDF($venta)
    {
        try {
            $query = DB::table('documentoventa as dv')
                ->join('detalledocumentoventa as dcv', 'dcv.CodigoVenta', '=', 'dv.Codigo')
                ->join('motivonotacredito as MOTIVO', 'MOTIVO.Codigo', '=', 'dv.CodigoMotivoNotaCredito')
                ->join('documentoventa as venta', 'venta.Codigo', '=', 'dv.CodigoDocumentoReferencia')
                ->join('sedesrec as s', 's.Codigo', '=', 'dv.CodigoSede')
                ->join('empresas as e', 'e.Codigo', '=', 's.CodigoEmpresa')
                ->leftJoin('clienteempresa as ce', 'dv.CodigoClienteEmpresa', '=', 'ce.Codigo')
                ->leftJoin('personas as clienteN', 'dv.CodigoPersona', '=', 'clienteN.Codigo')
                ->join('personas as vendedor', 'vendedor.Codigo', '=', 'dv.CodigoTrabajador')
                ->leftJoin('tipo_documentos as td', 'td.Codigo', '=', 'clienteN.CodigoTipoDocumento')
                ->leftJoin('devolucionnotacredito as dnc', 'dnc.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('egreso as eg', 'eg.Codigo', '=', 'dnc.Codigo')
                ->leftJoin('mediopago as mp', 'mp.Codigo', '=', 'eg.CodigoMedioPago')
                ->select([
                    'e.Nombre as empresaNombre',
                    'e.Ruc as ruc',
                    'e.Direccion as direccion',
                    DB::raw("'Lambayeque' AS departamento"),
                    DB::raw("'Chiclayo' AS provincia"),
                    DB::raw("'Chiclayo' AS distrito"),
                    's.Nombre AS sede',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("
                            CASE 
                                WHEN dv.CodigoPersona IS NULL THEN ce.RazonSocial 
                                WHEN dv.CodigoClienteEmpresa IS NULL THEN CONCAT(clienteN.Nombres, ' ', clienteN.Apellidos) 
                            END AS cliente
                        "),
                    DB::raw("
                            CASE 
                                WHEN dv.CodigoPersona IS NULL THEN 'RUC' 
                                WHEN dv.CodigoClienteEmpresa IS NULL THEN td.Siglas 
                            END AS documentoIdentidad
                        "),
                    DB::raw("
                            CASE 
                                WHEN dv.CodigoPersona IS NULL THEN ce.RUC 
                                WHEN dv.CodigoClienteEmpresa IS NULL THEN clienteN.NumeroDocumento 
                            END AS numDocumento
                        "),
                    DB::raw("
                            CASE 
                                WHEN dv.CodigoPersona IS NULL THEN ce.Direccion 
                                WHEN dv.CodigoClienteEmpresa IS NULL THEN clienteN.Direccion 
                            END AS clienteDireccion
                        "),
                    'mp.Nombre AS FormaPago',
                    'dv.Fecha AS fechaEmision',
                    DB::raw("'Soles' AS moneda"),
                    DB::raw('ABS(dv.MontoTotal) AS totalPagar'),
                    DB::raw('ABS(dv.IGVTotal) AS igv'),
                    DB::raw('ABS(dv.TotalGravado) AS opGravadas'),
                    DB::raw("CONCAT(vendedor.Nombres, ' ', vendedor.Apellidos) AS vendedor"),
                    'venta.Serie AS docRefSerie',
                    DB::raw("LPAD(venta.Numero, 8, '0') AS docRefNumero"),
                    'venta.Fecha AS docRefFecha',
                    'MOTIVO.Nombre as motivo'
                ])
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();

            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->select([
                    DB::raw("ABS(ddv.Cantidad) as cantidad"),
                    DB::raw("'unidad' AS unidad"),
                    'ddv.Descripcion as descripcion',
                    DB::raw("ABS(((ddv.MontoTotal + ddv.Descuento) / ddv.Cantidad)) as precioUnitario"),
                    DB::raw("ABS(ddv.Descuento) as descuento"),
                    DB::raw("ABS(ddv.MontoTotal) as total")
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();
            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    public function notaVentaPDF($venta)
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
                    'td.Siglas as documentoIdentidad',
                    'p.NumeroDocumento as numDocumento',
                    'mp.Nombre as FormaPago',
                    'p.Direccion as clienteDireccion',
                    'dv.Fecha as fechaEmision',
                    DB::raw("'Soles' as moneda"),
                    'dv.MontoTotal as totalPagar',
                    'dv.IGVTotal as igv',
                    'dv.TotalGravado as opGravadas',
                    DB::raw("CONCAT(vendedor.Nombres, ' ', vendedor.Apellidos) as vendedor"),
                    DB::raw("(SELECT SUM(Descuento) FROM detalledocumentoventa WHERE CodigoVenta = dv.Codigo) AS descuentoTotal")
                )
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();

            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->select([
                    'ddv.Cantidad as cantidad',
                    DB::raw("'unidad' AS unidad"),
                    'ddv.Descripcion as descripcion',
                    DB::raw("(ddv.MontoTotal / ddv.Cantidad) as precioUnitario"),
                    'ddv.Descuento as descuento',
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
