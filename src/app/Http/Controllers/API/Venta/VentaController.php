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
use App\Models\Recaudacion\FacturacionElectronica\EnvioFacturacion;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\Pago;
use App\Models\Recaudacion\PagoDocumentoVenta;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use App\Models\Recaudacion\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    private function formato4($numero)
    {
        return number_format((float)$numero, 4, '.', '');
    }

    public function registrarEnvio(array $data)
    {
        // $fechaActual = date('Y-m-d');

        // $data['Fecha'] = $fechaActual;
        try {
            EnvioFacturacion::create($data);
            //log info
            Log::info('Envio de factura electronica registrado correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarEnvio',
                'Datos' => $data,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return [
                'message' => 'Envio de la factura electronica registrado correctamente.'
            ];
        } catch (\Exception $e) {
            Log::error('Error al registrar el envio de la factura electronica.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarEnvio',
                'Comando' => $data,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return [
                'message' => 'Error al registrar el envio de la factura electronica.',
                'error' => $e->getMessage()
            ];
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

                //log warning

                Log::warning('Datos del emisor no encontrados.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'detallesFacturacionElectronica',
                    'CodigoSede' => $ventaData['CodigoSede'],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

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
                        DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as Nombres"),
                        'td.CodigoSUNAT',
                        'p.Direccion',
                        'p.Correo'
                    )
                    ->first();
            } elseif ($ventaData['CodigoClienteEmpresa'] != null) {
                $cliente = DB::table('clienteempresa')
                    ->where('Codigo', $ventaData['CodigoClienteEmpresa'])
                    ->select(
                        'RUC as NumeroDocumento',
                        'RazonSocial as Nombres',
                        DB::raw("6 as CodigoSUNAT"), // Asumiendo que 6 es el código SUNAT para RUC
                        'Direccion',
                        DB::raw("'' as Correo")
                    )
                    ->first();
            }


            // Parsear fecha y hora
            $fechaHora = Carbon::parse($ventaData['Fecha']);
            $fechaEmision = $fechaHora->format('Y-m-d');
            $horaEmision = $fechaHora->format('H:i:s');

            // Procesar detalles
            $detallesFormateados = [];

            foreach ($detallesVenta as $i => $detalle) {

                $datosProductoSede = DB::table('sedeproducto as sp')
                    ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                    ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                    ->join('tipogravado as tg', 'sp.CodigoTipoGravado', '=', 'tg.Codigo')
                    ->where('p.Codigo', $detalle['CodigoProducto'])
                    ->where('sp.CodigoSede', $ventaData['CodigoSede'])
                    ->select([
                        'um.CodigoSUNAT as unidadMedida',
                        'tg.CodigoSUNAT as tipoGravado',
                        'p.Tipo',
                        'p.Codigo'
                    ])
                    ->first();

                    $detallesFormateados[] = [
                        'num_lin_item'        => $i + 1,
                        'cod_unid_item'       => $datosProductoSede->unidadMedida,
                        'cant_unid_item'      => $this->formato4($detalle['Cantidad'] ?? 0),
                        'val_vta_item'        => $this->formato4(abs(($detalle['MontoTotal'] ?? 0) - ($detalle['MontoIGV'] ?? 0))),
                        'cod_tip_afect_igv_item' => $datosProductoSede->tipoGravado,
                        'prc_vta_unit_item'   => $this->formato4(abs(($detalle['MontoTotal'] ?? 0) / max($detalle['Cantidad'] ?? 1, 1))),
                        'mnt_dscto_item'      => $this->formato4(abs($detalle['Descuento'] ?? 0)),
                        'mnt_igv_item'        => $this->formato4(abs($detalle['MontoIGV'] ?? 0)),
                        'txt_descr_item'      => $detalle['Descripcion'] ?? 'Producto sin descripción',
                        'val_unit_item'       => $this->formato4(abs((($detalle['MontoTotal'] ?? 0) - ($detalle['MontoIGV'] ?? 0)) / max($detalle['Cantidad'] ?? 1, 1))),
                        'importe_total_item'  => $this->formato4(abs($detalle['MontoTotal'] ?? 0)),
                        'cod_item' => $datosProductoSede->Tipo . str_pad($detalle['CodigoProducto'], 5, '0', STR_PAD_LEFT),
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
                    $identificador = 'CC'; // Nota de crédito
                    break;
                case '08':
                    $identificador = 'DC'; // Nota de debito
                    break;

                default:
                    $identificador = 'NN'; //NO ENCONTRADO;
            }

            if ($tipoDocumentoVenta->CodigoSUNAT == '07' || $tipoDocumentoVenta->CodigoSUNAT == '08') {
                $debito_credito_nota = DB::table('documentoventa as nc')
                    ->join('documentoventa as dr', 'nc.CodigoDocumentoReferencia', '=', 'dr.Codigo')
                    ->join('motivonotacredito as mnc', 'nc.CodigoMotivoNotaCredito', '=', 'mnc.Codigo')
                    ->join('tipodocumentoventa as tdv', 'dr.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')
                    ->where('nc.CodigoDocumentoReferencia', $ventaData['CodigoDocumentoReferencia'])
                    ->select([
                        'tdv.CodigoSUNAT as DocumentoCodigo',
                        'dr.Serie',
                        'dr.Numero',
                        'dr.Fecha',
                        'mnc.CodigoSUNAT as MotivoCodigo',
                        'mnc.Nombre as Motivo',
                    ])
                    ->first();
            }

            // Construir el JSON final
            $facturacionElectronica = array_merge(
                [
                    // Datos generales...
                    'identificador' => $identificador,
                    'fec_emis' => $fechaEmision,
                    'hora_emis' => $horaEmision,
                    'txt_serie' => $ventaData['Serie'] ?? '',
                    'txt_correlativo' => $ventaData['Numero'] ?? '',
                    'cod_tip_cpe' =>  $tipoDocumentoVenta->CodigoSUNAT,
                    'cod_mnd' => 'PEN',
                    'cod_tip_escenario' => '03',
                    'cod_cliente_emis' => $datosEmisor->cod_cliente_emis,
                    'num_ruc_emis' => $datosEmisor->num_ruc_emis,
                    'nom_rzn_soc_emis' => $datosEmisor->nom_rzn_soc_emis,
                    'cod_tip_nif_emis' => $datosEmisor->cod_tip_nif_emis,
                    'cod_loc_emis' => 1, //vALIDAR LUEGO
                    'cod_ubi_emis' => $datosEmisor->cod_ubi_emis,
                    'txt_dmcl_fisc_emis' => $datosEmisor->txt_dmcl_fisc_emis,
                    'txt_prov_emis' => $datosEmisor->txt_prov_emis,
                    'txt_dpto_emis' => $datosEmisor->txt_dpto_emis,
                    'txt_distr_emis' => $datosEmisor->txt_distr_emis,

                    // Cliente
                    'num_iden_recp' => $cliente->NumeroDocumento ?? null,
                    'cod_tip_nif_recp' => $cliente->CodigoSUNAT ?? null,
                    'nom_rzn_soc_recp' => $cliente->Nombres ?? null,
                    'txt_dmcl_fisc_recep' => $cliente->Direccion ?? null,
                    'txt_correo_adquiriente' => $cliente->Correo ?? null,

                    // Venta
                    'mnt_tot_gravadas'     => round(abs($ventaData['TotalGravado'] ?? 0), 4),
                    'mnt_tot_inafectas'    => round(abs($ventaData['TotalInafecto'] ?? 0), 4),
                    'mnt_tot_exoneradas'   => round(abs($ventaData['TotalExonerado'] ?? 0), 4),
                    'mnt_tot_gratuitas'    => round(abs($ventaData['TotalGratis'] ?? 0), 4),
                    'mnt_tot_desc_global'  => 0.00,
                    'mnt_tot_igv'          => round(abs($ventaData['IGVTotal'] ?? 0), 4),
                    'mnt_tot'              => round(abs($ventaData['MontoTotal'] ?? 0), 4),
                    'mnt_tot_base_imponible' => 0.00,
                    'mnt_tot_percepcion' => 0.00,
                    'mnt_tot_a_percibir' => 0.00,
                    'porcentaje_dscto' => '',
                    'cod_operacion' => '0101',
                    'mnt_anticipo' => 0.00,
                    'mnt_otros_cargos' => 0.00,
                    'tipo_percepcion' => '',
                    'porcentaje_percepcion' => '',
                    'tipo_cambio' => 0.00,
                    'txt_observ' => '',

                    'detalles' => $detallesFormateados
                ],
                in_array($tipoDocumentoVenta->CodigoSUNAT, ['07', '08']) ? [ // Si es NC o ND
                    'cod_tip_nc_nd_ref' => $debito_credito_nota->MotivoCodigo, // Código del tipo de documento de referencia
                    'txt_serie_ref' => $debito_credito_nota->Serie, // Serie del comprobante de referencia
                    'txt_correlativo_cpe_ref' => $debito_credito_nota->Numero, // Correlativo del comprobante de referencia
                    'fec_emis_ref' => $debito_credito_nota->Fecha, // Fecha de emisión del comprobante de referencia
                    'cod_cpe_ref' => $debito_credito_nota->DocumentoCodigo, // Código SUNAT del comprobante de referencia
                    'txt_sustento' => $debito_credito_nota->Motivo // Motivo de la nota
                ] : []
            );

            // Enviar el JSON a la API de facturación electrónica
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $datosEmisor->TokenPSE
            ])->post(env('PSE_API_URL'), $facturacionElectronica);


            if ($response->successful()) {
                $data = $response->json();
                $mensaje = $data['Mensaje'] ?? 'Mensaje no disponible';
                $resultado = $data['Resultado'] ?? false;

                //log info  
                Log::info('Factura electrónica enviada correctamente.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'detallesFacturacionElectronica',
                    'Resultado' => $resultado,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return [
                    'success' => $resultado,
                    'Mensaje' => $mensaje,
                    'JSON' => $facturacionElectronica,
                    'Estado' => $resultado ? 'A' : 'R',
                ];
            } else {
                $status = $response->status();
                $mensajeError = $status === 401
                    ? '401 - No autorizado'
                    : '500 - Error interno del servidor';

                //log error
                Log::error('Error al enviar la factura electrónica.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'detallesFacturacionElectronica',
                    'Mensaje' => $mensajeError,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return [
                    'success' => false,
                    'Mensaje' => $mensajeError,
                    'JSON' => $facturacionElectronica,
                    'Estado' => 'N',
                ];
            }
        } catch (\Exception $e) {
            // Manejo de errores

            Log::error('Error al generar el JSON de facturación electrónica.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'detallesFacturacionElectronica',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return [
                'success' => false,
                'Mensaje' => 'Error Interno',
                'JSON' => json_encode('Error JSON'),
                'Estado' => 'N',
                'error' => $e->getMessage()
            ];
        }
    }


    public function anularFacturacionElectronica($codigoVenta, $anulacionData, $codigoAnulacion)
    {

        try {

            $nombreMotivo = DB::table('anulacion as a')
                ->join('motivoanulacion as ma', 'a.CodigoMotivo', '=', 'ma.Codigo')
                ->where('a.Codigo', $codigoAnulacion)
                ->value('ma.Nombre');

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

            // switch ($datosVenta->CodigoSUNAT) {
            //     case '01':
            //         $identificador = 'FC'; // Factura
            //         break;
            //     case '03':
            //         $identificador = 'BC'; // Boleta de venta
            //         break;
            //     case '07':
            //         $identificador = 'CC'; // Nota de crédito
            //         break;
            //     case '08':
            //         $identificador = 'DC'; // Nota de debito
            //         break;
            // default:
            //     return response()->json(['error' => 'Tipo de documento no soportado.'], 400);
            // } 

            $anulacionJSON = [
                'identificador' => 'CB', //Para todo incluyendo Bolete Factura etc (creo)
                'fec_emis' => $datosVenta->Fecha,
                'fec_gener_baja' => $anulacionData['Fecha'],
                'cod_tip_escenario' => '03',
                'txt_serie' => $datosVenta->Serie,
                'cod_iden_cb' => 'C', // Ni idea
                'cod_cliente_emis' => $datosEmisor->cod_cliente_emis,
                'num_ruc_emis' => $datosEmisor->num_ruc_emis,
                'txt_correlativo' => $datosVenta->Numero,
                'cod_tip_cpe' => $datosVenta->CodigoSUNAT,
                'txt_descr_mtvo_baja' =>  $nombreMotivo
            ];


            // Enviar el JSON a la API de facturación electrónica
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $datosEmisor->TokenPSE
            ])->post(env('PSE_API_URL'), $anulacionJSON);


            if ($response->successful()) {
                $data = $response->json();
                $mensaje = $data['Mensaje'] ?? 'Mensaje no disponible';
                $resultado = $data['Resultado'] ?? false;

                //log info
                Log::info('Anulación de factura electrónica enviada correctamente.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'anularFacturacionElectronica',
                    'Mensaje' => $mensaje,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return [
                    'success' => $resultado,
                    'Mensaje' => $mensaje,
                    'JSON' => $anulacionJSON,
                    'Estado' => $resultado ? 'A' : 'R',
                ];
            } else {

                $status = $response->status();
                $mensajeError = $status === 401
                    ? '401 - No autorizado'
                    : '500 - Error interno del servidor';

                //log info

                Log::error('Error al enviar la anulación de factura electrónica.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'anularFacturacionElectronica',
                    'Mensaje' => $mensajeError,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return [
                    'success' => false,
                    'Mensaje' => $mensajeError,
                    'JSON' => $anulacionJSON,
                    'Estado' => 'N',
                ];
            }
        } catch (\Exception $e) {

            // Manejo de errores
            Log::error('Error al generar el JSON de anulación electrónica.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'anularFacturacionElectronica',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return  ['error' => 'Error al generar el JSON de anulación electrónica.', 'bd' => $e->getMessage()];
        }
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

            if (!$resultados) {

                //log warning
                Log::warning('No se encontraron cuentas de detracción para la empresa especificada.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'cuentaDetraccion',
                    'CodigoEmpresa' => $empresa,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'No se encontraron cuentas de detracción para la empresa especificada.'], 404);
            }

            //log info
            Log::info('Cuentas de detracción obtenidas correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'cuentaDetraccion',
                'CodigoEmpresa' => $empresa,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($resultados, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al obtener cuentas de detracción.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'cuentaDetraccion',
                'Codigo' => $empresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

                //log warning
                Log::warning('Pago no encontrado.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'anularPago',
                    'CodigoPago' => $pago,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'Pago no encontrado.'], 404);
            }

            if ($pagoData->Vigente == 0) {

                //log warning
                Log::warning('El pago ya fue anulado.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'anularPago',
                    'CodigoPago' => $pago,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'El pago ya fue anulado.'], 400);
            }

            $estadoCaja = ValidarFecha::obtenerFechaCaja($pagoData->CodigoCaja);

            if ($estadoCaja->Estado == 'C') {

                //log warning
                Log::warning(
                    'No se puede anular el pago porque la caja está cerrada.',
                    [
                        'Controlador' => 'VentaController',
                        'Metodo' => 'anularPago',
                        'CodigoPago' => $pago,
                        'Mensaje' => __('mensajes.error_anulacion_pago_caja'),
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    ]
                );

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

            //log info
            Log::info('Pago anulado correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'anularPago',
                'CodigoPago' => $pago,
                'CodigoVenta' => $venta,
                'MontoAnulado' => $monto,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['message' => 'Pago anulada correctamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al anular el pago.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'anularPago',
                'CodigoPago' => $pago,
                'CodigoVenta' => $venta,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                ->select('p.Codigo', 'mp.Nombre', 'pdv.Monto', 'p.Fecha', 'mp.CodigoSUNAT', 'p.Vigente')
                ->get();

            //log info
            Log::info('Pagos asociados a la venta obtenidos correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'listarPagosAsociados',
                'CodigoVenta' => $venta,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($pago, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al obtener los pagos asociados a la venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'listarPagosAsociados',
                'CodigoVenta' => $venta,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function registrarPagoVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $pagoData = $request->input('pago');
        $ventaData = $request->input('venta');

        // if (!$ventaData) {
        //     return response()->json(['error' => 'No se ha encontrado la venta.'], 400);
        // }

        // if ($pagoData['CodigoMedioPago'] == 1) {
        //     $pagoData['Fecha'] = $fecha;
        //     $pagoData['CodigoCuentaBancaria'] = null;
        // }

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

            //log warning
            Log::warning('La fecha de recaudación no puede ser mayor a la fecha de apertura de caja.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarPagoVenta',
                'CodigoCaja' => $pagoData['CodigoCaja'],
                'FechaCaja' => $fechaCajaVal,
                'FechaRecaudacion' => $fechaVentaVal,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

            if ($ventaData && $ventaData > 0) {

                PagoDocumentoVenta::create([
                    'CodigoPago' => $codigoPago,
                    'CodigoDocumentoVenta' => $ventaData,
                    'Monto' => $pagoData['Monto'],
                ]);

                DB::table('documentoventa')
                    ->where('Codigo', $ventaData)
                    ->increment('MontoPagado', $pagoData['Monto']);
            }

            DB::commit();

            //log info
            Log::info('Pago registrado correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarPagoVenta',
                'CodigoPago' => $codigoPago,
                'CodigoVenta' => $ventaData,
                'Monto' => $pagoData['Monto'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['message' => 'Pago registrada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al registrar el pago.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarPagoVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

            //log warning
            Log::warning('La fecha de la venta no puede ser mayor a la fecha de apertura de caja.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarNotaCredito',
                'CodigoCaja' => $ventaData['CodigoCaja'],
                'FechaCaja' => $fechaCajaVal,
                'FechaVenta' => $fechaVentaVal,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

                    //log warning
                    Log::warning('No hay suficiente Efectivo en caja para el egreso.', [
                        'Controlador' => 'VentaController',
                        'Metodo' => 'registrarNotaCredito',
                        'CodigoCaja' => $ventaData['CodigoCaja'],
                        'MontoEgreso' => $dataEgreso['Monto'],
                        'TotalCaja' => $total,
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    ]);

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

                //log warning
                Log::warning('La fecha de la venta no puede ser mayor a la fecha de apertura de caja.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'registrarNotaCredito',
                    'CodigoCaja' => $ventaData['CodigoCaja'],
                    'FechaCaja' => $fechaCajaVal,
                    'FechaPago' => $fechaPagoVal,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

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

            //log info
            Log::info('Nota de Crédito registrada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarNotaCredito',
                'CodigoVenta' => $ventaCreada->Codigo,
                'MontoTotal' => $ventaData['MontoTotal'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al registrar la Nota de Crédito.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarNotaCredito',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json([
                'error' => 'Ocurrió un error al registrar la Venta.',
                'db' => $e->getMessage()
            ], 500);
        }

        // ✅ Ahora sí, fuera de la transacción: procesamiento de facturación electrónica

        $notaVenta = DB::table('tipodocumentoventa')
            ->where('Codigo', $ventaData['CodigoTipoDocumentoVenta'])
            ->selectRaw('CodigoSUNAT IS NOT NULL AS Existe')
            ->value('Existe');

        if ($notaVenta != 0) {

            try {

                $data = $this->detallesFacturacionElectronica($ventaData, $detallesVentaData, $ventaCreada->Codigo);

                $dataEnvio = [
                    'Tipo' => 'C',
                    'JSON' => is_array($data['JSON']) ? json_encode($data['JSON']) : $data['JSON'],
                    'URL' => env('PSE_API_URL'),
                    'Fecha' => $ventaData['Fecha'],
                    'CodigoTrabajador' => $ventaData['CodigoTrabajador'],
                    'Estado' => $data['Estado'],
                    'CodigoDocumentoVenta' => $ventaCreada->Codigo,
                    'Mensaje' => $data['Mensaje'],
                    'CodigoSede' => $ventaData['CodigoSede']
                ];

                $registroEnvio = $this->registrarEnvio($dataEnvio);
            } catch (\Exception $fe) {
                // ⚠️ Aquí NO cortamos el flujo, solo registramos error de facturación
                $data = [
                    'success' => false,
                    'Mensaje' => 'Error en la facturación electrónica.',
                    'error' => $fe->getMessage()
                ];
                $registroEnvio = null;
            }
        }

        //log info
        Log::info('Detalles de facturación electrónica procesados.', [
            'Controlador' => 'VentaController',
            'Metodo' => 'registrarNotaCredito',
            'CodigoVenta' => $ventaCreada->Codigo,
            'success' => $data['success'],
            'Mensaje' => $data['Mensaje'],
            'error' => $data['error'] ?? 'Sin error',
            'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
        ]);

        return response()->json([
            'message' => 'Nota de Crédito registrada correctamente.',
            'facturacion' => [
                'success' => $data['success'],
                'Mensaje' => $data['Mensaje'],
                'error' => $data['error'] ?? 'Sin error',
            ],
            'envio' => $registroEnvio,
        ], 201);
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

            //log warning
            Log::warning('La fecha de la venta no puede ser mayor a la fecha de apertura
            de caja.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarVenta',
                'CodigoCaja' => $ventaData['CodigoCaja'],
                'FechaCaja' => $fechaCajaVal,
                'FechaVenta' => $fechaVentaVal,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

                //log warning
                Log::warning('La fecha de la venta no puede ser mayor a la fecha de apertura
                de caja.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'registrarVenta',
                    'CodigoCaja' => $ventaData['CodigoCaja'],
                    'FechaCaja' => $fechaCajaVal,
                    'FechaPago' => $fechaPagoVal,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'La fecha de la venta no puede ser mayor a la fecha de apertura la caja.'], 400);
            }
        }


        DB::beginTransaction();

        try {

            $numero = DB::table('documentoventa')
                ->where('Serie', $ventaData['Serie'])
                ->where('CodigoSede', $ventaData['CodigoSede'])
                ->max('Numero');

            $ventaData['Numero'] = $numero ? $numero + 1 : 1;

            $ventaCreada = Venta::create($ventaData);

            $ventaData['TotalDescuento'] = 0;

            foreach ($detallesVentaData as $i => $detalle) {
                $detalle['Numero'] = $i + 1; 
                $detalle['CodigoVenta'] = $ventaCreada->Codigo;
                
                if (!isset($detalle['Descuento'])) {
                    $detalle['Descuento'] = 0;
                }
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
                        ->where('Stock', '>=', $cantidadReducir)
                        ->decrement('Stock', $cantidadReducir);
                }
            }

            DB::commit(); // ✅ Solo si todo lo anterior fue exitoso

            //log info
            Log::info('Venta registrada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarVenta',
                'CodigoVenta' => $ventaCreada->Codigo,
                'MontoTotal' => $ventaData['MontoTotal'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al registrar la Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json([
                'error' => 'Ocurrió un error al registrar la Venta.',
                'db' => $e->getMessage()
            ], 500);
        }

        // ✅ Ahora sí, fuera de la transacción: procesamiento de facturación electrónica

        $notaVenta = DB::table('tipodocumentoventa')
            ->where('Codigo', $ventaData['CodigoTipoDocumentoVenta'])
            ->selectRaw('CodigoSUNAT IS NOT NULL AS Existe')
            ->value('Existe');

        if ($notaVenta != 0) {
            try {
                $data = $this->detallesFacturacionElectronica($ventaData, $detallesVentaData, $ventaCreada->Codigo);

                $dataEnvio = [
                    'Tipo' => 'F',
                    'JSON' => is_array($data['JSON']) ? json_encode($data['JSON']) : $data['JSON'],
                    'URL' => env('PSE_API_URL'),
                    'Fecha' => $ventaData['Fecha'],
                    'CodigoTrabajador' => $ventaData['CodigoTrabajador'],
                    'Estado' => $data['Estado'],
                    'CodigoDocumentoVenta' => $ventaCreada->Codigo,
                    'Mensaje' => $data['Mensaje'],
                    'CodigoSede' => $ventaData['CodigoSede']
                ];

                $registroEnvio = $this->registrarEnvio($dataEnvio);
            } catch (\Exception $fe) {

                //log error
                Log::error('Error al procesar la facturación electrónica.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'registrarVenta',
                    'Data' => $dataEnvio,
                    'mensaje' => $fe->getMessage(),
                    'linea' => $fe->getLine(),
                    'archivo' => $fe->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                // ⚠️ Aquí NO cortamos el flujo, solo registramos error de facturación
                $data = [
                    'success' => false,
                    'Mensaje' => 'Error en la facturación electrónica.',
                    'error' => $fe->getMessage()
                ];
                $registroEnvio = null;
            }
        } else {
            //log info
            Log::info('Venta registrada sin necesidad de facturación electrónica.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'registrarVenta',
                'CodigoVenta' => $ventaCreada->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json([
                'message' => 'Venta registrada correctamente.',
            ], 201);
        }

        //log info
        Log::info('Detalles de facturación electrónica procesados.', [
            'Controlador' => 'VentaController',
            'Metodo' => 'registrarVenta',
            'CodigoVenta' => $ventaCreada->Codigo,
            'success' => $data['success'],
            'Mensaje' => $data['Mensaje'],
            'error' => $data['error'] ?? 'Sin error',
            'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
        ]);

        return response()->json([
            'message' => 'Venta registrada correctamente.',
            'facturacion' => [
                'success' => $data['success'],
                'Mensaje' => $data['Mensaje'],
                'error' => $data['error'] ?? 'Sin error',
            ],
            'envio' => $registroEnvio,
        ], 201);
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
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Apellidos, ' ', p.Nombres)
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
                    DB::raw("CONCAT(medico.Apellidos, ' ', medico.Nombres) AS NombreMedico"),
                    DB::raw('COALESCE(dv.CodigoPaciente, 0) AS CodigoPaciente'),
                    DB::raw("COALESCE(CONCAT(paciente.Apellidos, ' ', paciente.Nombres), '') AS NombrePaciente"),
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
                ->leftJoin('contratoproducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
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

            //log info
            Log::info('Consulta de Documento de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarDocumentoVenta',
                'CodigoVenta' => $CodVenta,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['venta' => $venta, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Documento de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarDocumentoVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                    DB::raw("CONCAT(PACIENTE.Apellidos, ' ', PACIENTE.Nombres) as NombrePaciente"),
                    DB::raw("CONCAT(tdPaciente.Siglas, ': ', PACIENTE.NumeroDocumento) as DocumentoPaciente"),
                    'CONTRATO.CodigoMedico',
                    DB::raw("CONCAT(MEDICO.Apellidos, ' ', MEDICO.Nombres) as NombreMedico"),
                    DB::raw("COALESCE(VENTA.CodigoClienteEmpresa, 0) as CodigoEmpresa"),
                    DB::raw("COALESCE(VENTA.CodigoPersona, 0) as CodigoPersona"),
                    DB::raw("COALESCE(CONCAT(CLIENTE.Apellidos, ' ', CLIENTE.Nombres), EMPRESA.RazonSocial, '') as NombreCompleto"),
                    DB::raw("COALESCE(CONCAT(td.Siglas, ': ', CLIENTE.NumeroDocumento), CONCAT( 'RUC' , ': ' , EMPRESA.Ruc), '') as DocumentoCompleto")
                )
                ->first();


            $detalle = DB::table('producto as P')
                ->joinSub(
                    DB::table('detallecontrato as DC')
                        ->join('contratoproducto as CONTRATO', 'CONTRATO.Codigo', '=', 'DC.CodigoContrato') // ⬅️ Agregado

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
                        ->groupBy('DC.CodigoProducto', 'DC.Descripcion', 'DC.Codigo', 'DC.Descuento', 'CONTRATO.CodigoSede') // ⬅️ Agregado
                        ->select(
                            'DC.CodigoProducto',
                            'DC.Descripcion',
                            'DC.Codigo',
                            'DC.Descuento',
                            'CONTRATO.CodigoSede', // ⬅️ Agregado
                            DB::raw('SUM(DC.Cantidad) - COALESCE(SUM(Bol.CantidadBoleteada), 0) AS Cantidad'),
                            DB::raw('SUM(DC.MontoTotal) - COALESCE(SUM(Bol.MontoBoleteado), 0) AS Monto')
                        ),
                    'S',
                    'P.Codigo',
                    '=',
                    'S.CodigoProducto'
                )
                ->join('sedeproducto as SP', function ($join) {
                    $join->on('SP.CodigoProducto', '=', 'P.Codigo')
                        ->on('SP.CodigoSede', '=', 'S.CodigoSede');
                })
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


            //log info
            Log::info('Consulta de Contrato Producto realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarDatosContratoProducto',
                'CodigoContrato' => $idContrato,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['contrato' => $contrato, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Datos de Contrato Producto.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarDatosContratoProducto',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                    ->select('p.Codigo', DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as NombreCompleto"), DB::raw('1 as TipoCliente'))
                    ->orderBy('p.Codigo')
                    ->first();
            }

            //log info
            Log::info('Consulta de Cliente realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'buscarCliente',
                'numDocumento' => $numDocumento,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($cliente, 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al buscar Cliente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'buscarCliente',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
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
                ->where('p.Nombre', 'LIKE', "%{$nombreProducto}%")
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

            //log info
            Log::info('Consulta de Productos realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'buscarProductos',
                'nombreProducto' => $nombreProducto,
                'codigoSede' => $sede,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);


            return response()->json($productos, 200);
        } catch (\Exception $e) {
            Log::error('Error al buscar Productos.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'buscarProductos',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function buscarVenta(Request $request)
    {

        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d');
        $fecha = $request->input('fecha');
        $fechaFin = $request->input('fechaFin');
        $estadoPago = $request->input('estadoPago');
        $codigoSede = $request->input('codigoSede');
        $nombre = $request->input('nombre');
        $documento = $request->input('documento');
        $docVenta = $request->input('docVenta');
        $tipoVenta = $request->input('tipoVenta'); // Normal o Corporativa ( N - C)
        try {

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
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Apellidos, ' ', p.Nombres)
                        WHEN ce.Codigo IS NOT NULL THEN ce.RazonSocial
                        ELSE 'No identificado'
                    END AS NombreCliente,
                    CASE 
                        WHEN (dv.CodigoMotivoNotaCredito IS NULL AND dv.CodigoContratoProducto IS NULL) THEN 'V'
                        WHEN (dv.CodigoMotivoNotaCredito IS NULL AND dv.CodigoContratoProducto IS NOT NULL) THEN 'C'
                        WHEN (dv.CodigoMotivoNotaCredito IS NOT NULL AND tdv.CodigoSUNAT IS NOT NULL) THEN 'N'
                        WHEN (dv.CodigoMotivoNotaCredito IS NOT NULL AND tdv.CodigoSUNAT IS NULL) THEN 'D' 
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
                        WHEN dv.CodigoMedico IS NULL THEN 1
                        WHEN EXISTS (
                            SELECT 1 
                            FROM comision c 
                            WHERE c.CodigoDocumentoVenta = dv.Codigo AND c.Vigente = 1
                        ) THEN 1
                        ELSE 0
                    END AS comision,
                    CASE 
                        WHEN tdv.CodigoSUNAT = '03' AND DATEDIFF(DATE(?), DATE(dv.Fecha)) <= 7 THEN '1'
                        WHEN tdv.CodigoSUNAT = '01' AND DATEDIFF(DATE(?), DATE(dv.Fecha)) <= 3 THEN '1'
                        WHEN tdv.CodigoSUNAT IS NULL THEN '1'
                        ELSE '0'
                    END AS Anular
                ", [$fechaActual, $fechaActual])
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'dv.CodigoTipoDocumentoVenta')
                ->leftJoin('personas as p', 'p.Codigo', '=', 'dv.CodigoPersona')
                ->leftJoin('clienteempresa as ce', 'ce.Codigo', '=', 'dv.CodigoClienteEmpresa')
                ->where('dv.CodigoSede', $codigoSede)
                //Filtro por tipo de venta traer todo incluyendo Null
                ->when($tipoVenta, function ($query) use ($tipoVenta) {
                    if ($tipoVenta === 'N') {
                        $query->where(function ($q) {
                            $q->where('dv.TipoVenta', 'N')
                            ->orWhereNull('dv.TipoVenta');
                        });
                    } else {
                        $query->where('dv.TipoVenta', $tipoVenta);
                    }
                })
                // Filtro por fecha o rango
                ->when($fecha, function ($query) use ($fecha, $fechaFin) {
                    if (empty($fechaFin)) {
                        $query->whereDate('dv.Fecha', $fecha);
                    } else {
                        $query->whereBetween('dv.Fecha', [$fecha, $fechaFin]);
                    }
                })

                // Filtro por tipo de documento de venta
                ->when($docVenta != 0, fn($query) => $query->where('dv.CodigoTipoDocumentoVenta', $docVenta))

                // Filtro por nombre del cliente
                ->when($nombre, function ($query) use ($nombre) {
                    $query->where(function ($q) use ($nombre) {
                        $q->where('p.Nombres', 'LIKE', "%$nombre%")
                        ->orWhere('p.Apellidos', 'LIKE', "%$nombre%")
                        ->orWhere('ce.RazonSocial', 'LIKE', "%$nombre%");
                    });
                })

                // Filtro por documento de identidad o RUC
                ->when($documento, function ($queryD) use ($documento) {
                    $queryD->where(function ($q) use ($documento) {
                        $q->where('p.NumeroDocumento', 'LIKE', "$documento%")
                        ->orWhere('ce.RUC', 'LIKE', "$documento%");
                    });
                })

                ->when($estadoPago == 1, function ($query) {
                    $query->whereRaw('IFNULL(dv.MontoTotal, 0) = IFNULL(dv.MontoPagado, 0)');
                })
                ->when($estadoPago == 2, function ($query) {
                    $query->whereRaw('IFNULL(dv.MontoTotal, 0) != IFNULL(dv.MontoPagado, 0)');
                })

                ->orderByDesc('dv.Codigo')
                ->get();

            //log info
            Log::info('Consulta de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'buscarVenta',
                'Data' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($venta);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al buscar Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'buscarVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

            //log info
            Log::info('Consulta de Serie Nota Crédito realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarSerieNotaCredito',
                'query' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);


            return response()->json($result);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Serie Nota Crédito.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarSerieNotaCredito',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

            //log info
            Log::info('Consulta de Serie realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarSerie',
                'query' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($result);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Serie.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarSerie',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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


            //log info
            Log::info('Consulta de Tipo Producto realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarTipoProducto',
                'query' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($result);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Tipo Producto.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarTipoProducto',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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

            //log info
            Log::info('Consulta de Número de Documento de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultaNumDocumentoVenta',
                'query' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            // Retornar la nueva serie y número
            return response()->json([
                'Numero' => $nuevoNumero
            ]);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Número de Documento de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultaNumDocumentoVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
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

            //log warning
            Log::warning('Intento de anulación de venta con código inválido.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'anularVenta',
                'Data' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => 'No se ha encontrado la venta a anular.'], 404);
        }

        DB::beginTransaction();
        try {

            $codigoSunat = DB::table('documentoventa as dv')
            ->join('tipodocumentoventa as tdv', 'dv.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')
            ->where('dv.Codigo', $codigoVenta)
            ->value('tdv.CodigoSUNAT');

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
            // cerrar el catch y abrir otro
            //Obtener la sede de la venta
            $respEnvio = null;
            if($codigoSunat) {
                $sede_venta = DB::table('documentoventa')
                    ->where('Codigo', $codigoVenta)
                    ->value('CodigoSede');

                // Generar JSON para anulacion electrónica con los datos que ya tenemos
                $dataFactura = $this->anularFacturacionElectronica($codigoVenta, $anulacionData, $anulacionCreada->Codigo);
                //Generar insert de la tabla del envio de la anulacion electronica
                $dataEnvio['Tipo'] = 'B';
                $dataEnvio['JSON'] = json_encode($dataFactura['JSON']);
                $dataEnvio['URL'] = env('PSE_API_URL');
                $dataEnvio['Fecha'] = $anulacionData['Fecha'];
                $dataEnvio['CodigoTrabajador'] = $anulacionData['CodigoTrabajador'];
                $dataEnvio['Estado'] = $dataFactura['Estado'];
                $dataEnvio['CodigoAnulacion'] = $anulacionCreada->Codigo;
                $dataEnvio['Mensaje'] = $dataFactura['Mensaje'];
                $dataEnvio['CodigoSede'] = $sede_venta;
                $respEnvio = $this->registrarEnvio($dataEnvio);


                //log info
                Log::info('Venta anulada correctamente.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'anularVenta',
                    'Data' => $request->all(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);
            }

            return response()->json([
                'message' => 'Venta anulada correctamente.',
                'facturacion' => [
                    'success' => $dataFactura['success'] ?? true,
                    'Mensaje' => $dataFactura['Mensaje'] ?? 'Anulación procesada correctamente',
                ],
                'envio' => $respEnvio ? $respEnvio : null,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al anular Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'anularVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                    //log warning
                    Log::warning('No se encontraron Series para el Documento de Venta.', [
                        'Controlador' => 'VentaController',
                        'Metodo' => 'serieCanje',
                        'Data' => $request->all(),
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    ]);

                    return response()->json(['error' => 'No se encontraron Series para este Documento de Venta'], 200);
                }
            } else {

                //log warning
                Log::warning('TipoProducto no encontrado para el Documento de Venta.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'serieCanje',
                    'Data' => $request->all(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                // Si TipoProducto es null o no se encuentra, manejar el caso
                return response()->json(['error' => 'No se encontraron Series para este Documento de Venta'], 200);
            }

            //log info
            Log::info('Consulta de Serie para Canje realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'serieCanje',
                'Data' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json($serie);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Serie para Canje.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'serieCanje',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function devolucionNotaCredito()
    {
        try {
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
                $nuevoDocumentoVenta = Venta::create([
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
                $nuevoDocumentoVenta->TotalDescuento = 0;

                foreach ($detalleVenta as $detalle) {

                    $nuevoDocumentoVenta->TotalDescuento += $detalle->Descuento * $detalle->Cantidad;

                    if (!isset($detalle->Descuento)) {
                        $detalle->Descuento = 0;
                    }

                    DB::table('detalledocumentoventa')->insert([
                        'Numero' => $detalle->Numero,
                        'Descripcion' => $detalle->Descripcion,
                        'Cantidad' => $detalle->Cantidad,
                        'MontoTotal' => $detalle->MontoTotal,
                        'MontoIGV' => $detalle->MontoIGV,
                        'CodigoVenta' => $nuevoDocumentoVenta->Codigo,
                        'CodigoProducto' => $detalle->CodigoProducto,
                        'Descuento' => $detalle->Descuento,
                        'CodigoTipoGravado' => $detalle->CodigoTipoGravado,
                    ]);
                }

                // 3. Actualizar el campo Vigente en documentoventa
                DB::table('documentoventa')
                    ->where('Codigo', $canjeData['CodigoDocumentoReferencia'])
                    ->update(['Vigente' => 0, 'Estado' => 'C', 'CodigoDocumentoReferencia' => $nuevoDocumentoVenta->Codigo]);

                // 4. Actualizar el pagodocumentoventa con el nuevo código generado
                DB::table('pagodocumentoventa')
                    ->where('CodigoDocumentoVenta', $canjeData['CodigoDocumentoReferencia'])
                    ->update(['CodigoDocumentoVenta' => $nuevoDocumentoVenta->Codigo]);


                // 5. Actualizar el detallecontrato con el nuevo código generado
                // DB::table('detalledocumentoventa')
                //     ->where('CodigoVenta', $canjeData['CodigoDocumentoReferencia'])
                //     ->update(['CodigoVenta' => $nuevoCodigoDocumentoVenta]);
                DB::commit();
            } else {

                DB::rollBack();

                //log warning
                Log::warning('No se encontró el documento de venta a canjear.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'canjearDocumentoVenta',
                    'Data' => $request->all(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['message' => 'No se encontró el documento de venta a canjear.'], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            //log error
            Log::error('Error al canjear Documento de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'canjearDocumentoVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json([
                'error' => 'Ocurrió un error al registrar la Venta.',
                'db' => $e->getMessage()
            ], 500);
        }

        try {

            $detalleVentaArray = collect($detalleVenta)->map(function ($item) {
                return (array) $item;
            })->toArray();

            // Generar JSON para facturación electrónica con los datos que ya tenemos

            $data = $this->detallesFacturacionElectronica($nuevoDocumentoVenta, $detalleVentaArray, $nuevoDocumentoVenta->Codigo);
            // Generar insert de la tabla del envio de la factura electronica
            $dataEnvio['Tipo'] = 'F';
            $dataEnvio['JSON'] = is_array($data['JSON']) ? json_encode($data['JSON']) : $data['JSON'];
            $dataEnvio['URL'] = env('PSE_API_URL');
            $dataEnvio['Fecha'] = $canjeData['Fecha'];
            $dataEnvio['CodigoTrabajador'] = $canjeData['CodigoTrabajador'];
            $dataEnvio['Estado'] = $data['Estado'];
            $dataEnvio['CodigoDocumentoVenta'] = $nuevoDocumentoVenta->Codigo;
            $dataEnvio['Mensaje'] = $data['Mensaje'];
            $dataEnvio['CodigoSede'] = $venta->CodigoSede;
            // 'success' => $resultado,
            // 'Mensaje' => $mensaje,
            // 'JSON' => $facturacionElectronica,
            // 'Estado' => 'A',

            $registroEnvio = $this->registrarEnvio($dataEnvio);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar el envío de la factura electrónica.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'canjearDocumentoVenta',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error al registrar la Venta.', 'db' => $e->getMessage()], 500);
        }

        //log info
        Log::info('Canje de Documento de Venta registrado correctamente.', [
            'Controlador' => 'VentaController',
            'Metodo' => 'canjearDocumentoVenta',
            'Data' => $request->all(),
            'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
        ]);

        return response()->json([
            'message' => 'Canje registrado correctamente.',
            'facturacion' => [
                'success' => $data['success'],
                'Mensaje' => $data['Mensaje'],
                'error' => $data['error'] ?? 'Sin error',
            ],
            'envio' => $registroEnvio,
        ], 201);
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
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Apellidos, ' ', p.Nombres)
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
                    DB::raw("COALESCE(CONCAT(paciente.Apellidos, ' ', paciente.Nombres), '') AS NombrePaciente"),
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

            //log info
            Log::info('Consulta de Nota de Crédito realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarNotaCredito',
                'query' => $request->all(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['venta' => $venta, 'detalle' => $detalle, 'devolucion' => $devoluciones], 200);
        } catch (\Exception $e) {
            //log error
            Log::error('Error al consultar Nota de Crédito.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarNotaCredito',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function consultarNotaCreditoVenta($CodVenta)
    {

        try {

            $venta = DB::table('documentoventa as dv')
                ->select(
                    'dv.Codigo',
                    'dv.CodigoSede',
                    DB::raw('DATE(dv.Fecha) as Fecha'),
                    DB::raw("
                    CASE 
                        WHEN p.Codigo IS NOT NULL THEN CONCAT(p.Apellidos, ' ', p.Nombres)
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
                    DB::raw("CONCAT(medico.Apellidos, ' ', medico.Nombres) AS NombreMedico"),
                    DB::raw('COALESCE(dv.CodigoPaciente, 0) AS CodigoPaciente'),
                    DB::raw("COALESCE(CONCAT(paciente.Apellidos, ' ', paciente.Nombres), '') AS NombrePaciente"),
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
                ->leftJoin('contratoproducto as cp', 'cp.Codigo', '=', 'dv.CodigoContratoProducto')
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
                ->where('SP.CodigoSede', $venta->CodigoSede)
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

            //log info
            Log::info('Consulta de Nota de Crédito de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarNotaCreditoVenta',
                'query' => ['CodVenta' => $CodVenta],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['venta' => $venta, 'detalle' => $detalle], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Nota de Crédito de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarNotaCreditoVenta',
                'query' => ['CodVenta' => $CodVenta],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function consultarMedicoVenta($codigo){

        try{
            $medico = DB::table('documentoventa as dv')
                ->leftJoin('personas as p', 'dv.CodigoMedico', '=', 'p.Codigo')
                ->where('dv.Codigo', $codigo)
                ->select('dv.CodigoMedico', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Nombres"))
                ->first();

            if (!$medico) {
                //log warning
                Log::warning('No se encontró el médico de la venta.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'consultarMedicoVenta',
                    'query' => ['codigo' => $codigo],
                ]);
            }

            //log info
            Log::info('Consulta de Médico de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarMedicoVenta',
                'query' => ['codigo' => $codigo],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json($medico, 200);

        }catch(\Exception $e){
            //log error
            Log::error('Error al consultar Médico de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarMedicoVenta',
                'query' => ['codigo' => $codigo],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
        }
    }


    public function actualizarMedicoVenta(Request $request){

        $venta = $request->input('Venta');
        $medico = $request->input('Medico');

        try{

        $existe = DB::table('comision')
            ->where('CodigoDocumentoVenta', $venta)
            ->where('Vigente', 1)
            ->exists();

            if (!$existe) {
                DB::table('documentoventa')
                    ->where('Codigo', $venta)
                    ->update(['CodigoMedico' => $medico]);

                //log info
                Log::info('Médico de Venta actualizado correctamente.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'actualizarMedicoVenta',
                    'query' => ['venta' => $venta, 'medico' => $medico],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['message' => 'Médico de Venta actualizado correctamente.'], 200);
            
            }else{
                //log warning
                Log::warning('No se puede actualizar el Médico de Venta porque ya existe una comisión vigente.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'actualizarMedicoVenta',
                    'query' => ['venta' => $venta, 'medico' => $medico],
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['message' => 'No se puede actualizar el Médico de Venta porque ya existe una comisión vigente.'], 400);
            }

        }catch(\Exception $e){
            //log error
            Log::error('Error al actualizar Médico de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'actualizarMedicoVenta',
                'query' => ['venta' => $venta, 'medico' => $medico],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json(['message' => 'Error al actualizar Médico de Venta.', 'error' => $e->getMessage()], 500);
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
                    'e.Departamento AS departamento',
                    'e.Provincia AS provincia',
                    'e.Distrito AS distrito',
                    's.Nombre AS sede',
                    's.Telefono1 AS telefono1',
                    's.Telefono2 AS telefono2',
                    's.Telefono3 AS telefono3',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as cliente"),
                    'td.Siglas as documentoIdentidad',
                    'p.NumeroDocumento as numDocumento',
                    'mp.Nombre as FormaPago',

                    DB::raw("CASE WHEN mp.CodigoSUNAT = '005' or mp.CodigoSUNAT = '006' THEN 1 ELSE 0 END as Tarjeta"),
                    'pg.NumeroOperacion as Operacion',
                    'pg.Lote',
                    'pg.Referencia',

                    'p.Direccion as clienteDireccion',
                    'dv.Fecha as fechaEmision',
                    DB::raw("'Soles' as moneda"),
                    'dv.MontoTotal as totalPagar',
                    'dv.IGVTotal as igv',
                    'dv.TotalGravado as opGravadas',
                    DB::raw("CONCAT(vendedor.Apellidos, ' ', vendedor.Nombres) as vendedor"),
                    DB::raw("(SELECT SUM(Descuento * Cantidad) FROM detalledocumentoventa WHERE CodigoVenta = dv.Codigo) AS descuentoTotal")
                )
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();


            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                ->select([
                    'ddv.Cantidad as cantidad',
                    'um.CodigoSUNAT AS unidad',
                    'ddv.Descripcion as descripcion',
                    DB::raw("( (ddv.MontoTotal + ddv.Descuento) / ddv.Cantidad) as precioUnitario"),
                    'ddv.Descuento as descuento',
                    'ddv.MontoTotal as total'
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();

            //log info
            Log::info('Consulta de Boleta de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'boletaVentaPDF',
                'query' => ['venta' => $venta],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Boleta de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'boletaVentaPDF',
                'query' => ['venta' => $venta],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                ->join('personas as paciente', 'paciente.Codigo', '=', 'dv.CodigoPaciente')
                ->join('personas as vendedor', 'vendedor.Codigo', '=', 'dv.CodigoTrabajador')
                ->join('tipo_documentos as tipoDocPaciente', 'tipoDocPaciente.Codigo', '=', 'paciente.CodigoTipoDocumento')
                ->select(
                    'dv.Vigente',
                    'e.Nombre as empresaNombre',
                    'e.Ruc as ruc',
                    'e.Direccion as direccion',
                    'e.Departamento AS departamento',
                    'e.Provincia AS provincia',
                    'e.Distrito AS distrito',
                    's.Nombre AS sede',
                    's.Telefono1 AS telefono1',
                    's.Telefono2 AS telefono2',
                    's.Telefono3 AS telefono3',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("CASE
                                WHEN dv.CodigoPersona IS NOT NULL THEN CONCAT(pEmp.Apellidos, ' ', pEmp.Nombres)
                                WHEN dv.CodigoClienteEmpresa IS NOT NULL THEN ce.RazonSocial
                                ELSE 'N/A'
                            END AS cliente"),
                    DB::raw("'RUC' AS documentoIdentidad"),
                    DB::raw("CASE
                                WHEN dv.CodigoPersona IS NOT NULL THEN pEmp.NumeroDocumento
                                WHEN dv.CodigoClienteEmpresa IS NOT NULL THEN ce.RUC
                                ELSE 'N/A'
                            END AS numDocumento"),
                    'mp.Nombre as FormaPago',
                    DB::raw("CASE WHEN mp.CodigoSUNAT = '005' or mp.CodigoSUNAT = '006' THEN 1 ELSE 0 END as Tarjeta"),
                    'pg.NumeroOperacion as Operacion',
                    'pg.Lote',
                    'pg.Referencia',
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
                    DB::raw("CONCAT(vendedor.Apellidos, ' ', vendedor.Nombres) as vendedor"),
                    DB::raw("(SELECT SUM(Descuento) FROM detalledocumentoventa WHERE CodigoVenta = dv.Codigo) AS descuentoTotal"),
                    DB::raw("CONCAT(paciente.Apellidos, ' ', paciente.Nombres) as NombrePaciente"),
                    'paciente.NumeroDocumento as DocumentoPaciente',
                    'tipoDocPaciente.Siglas as docIdentidadPaciente'
                )
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();

            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                ->select([
                    'ddv.Cantidad as cantidad',
                    'um.CodigoSUNAT AS unidad',
                    'ddv.Descripcion as descripcion',
                    DB::raw("( (ddv.MontoTotal + ddv.Descuento) / ddv.Cantidad) as precioUnitario"),
                    'ddv.Descuento as descuento',
                    'ddv.MontoTotal as total'
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();

            //log info
            Log::info('Consulta de Factura de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'facturaVentaPDF',
                'query' => ['venta' => $venta],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Factura de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'facturaVentaPDF',
                'query' => ['venta' => $venta],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                    'e.Departamento AS departamento',
                    'e.Provincia AS provincia',
                    'e.Distrito AS distrito',
                    's.Nombre AS sede',
                    's.Telefono1 AS telefono1',
                    's.Telefono2 AS telefono2',
                    's.Telefono3 AS telefono3',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("
                            CASE 
                                WHEN dv.CodigoPersona IS NULL THEN ce.RazonSocial 
                                WHEN dv.CodigoClienteEmpresa IS NULL THEN CONCAT(clienteN.Apellidos, ' ', clienteN.Nombres) 
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
                    DB::raw("CASE WHEN mp.CodigoSUNAT = '005' or mp.CodigoSUNAT = '006' THEN 1 ELSE 0 END as Tarjeta"),
                    'eg.NumeroOperacion as Operacion',
                    'eg.Lote',
                    'eg.Referencia',
                    'dv.Fecha AS fechaEmision',
                    DB::raw("'Soles' AS moneda"),
                    DB::raw('ABS(dv.MontoTotal) AS totalPagar'),
                    DB::raw('ABS(dv.IGVTotal) AS igv'),
                    DB::raw('ABS(dv.TotalGravado) AS opGravadas'),
                    DB::raw("CONCAT(vendedor.Apellidos, ' ', vendedor.Nombres) AS vendedor"),
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
                ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                ->select([
                    DB::raw("ABS(ddv.Cantidad) as cantidad"),
                    'um.CodigoSUNAT AS unidad',
                    'ddv.Descripcion as descripcion',
                    DB::raw("ABS(((ddv.MontoTotal + ddv.Descuento) / ddv.Cantidad)) as precioUnitario"),
                    DB::raw("ABS(ddv.Descuento) as descuento"),
                    DB::raw("ABS(ddv.MontoTotal) as total")
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();

            //log info
            Log::info('Consulta de Nota de Crédito realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'notaCreditoPDF',
                'query' => ['venta' => $venta],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al consultar Nota de Crédito.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'notaCreditoPDF',
                'query' => ['venta' => $venta],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

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
                    'e.Departamento AS departamento',
                    'e.Provincia AS provincia',
                    'e.Distrito AS distrito',
                    's.Nombre AS sede',
                    's.Telefono1 AS telefono1',
                    's.Telefono2 AS telefono2',
                    's.Telefono3 AS telefono3',
                    'dv.Serie AS serie',
                    DB::raw("LPAD(dv.Numero, 8, '0') AS numero"),
                    DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as cliente"),
                    'td.Siglas as documentoIdentidad',
                    'p.NumeroDocumento as numDocumento',
                    'mp.Nombre as FormaPago',
                    'p.Direccion as clienteDireccion',
                    'dv.Fecha as fechaEmision',
                    DB::raw("'Soles' as moneda"),
                    'dv.MontoTotal as totalPagar',
                    'dv.IGVTotal as igv',
                    'dv.TotalGravado as opGravadas',
                    DB::raw("CONCAT(vendedor.Apellidos, ' ', vendedor.Nombres) as vendedor"),
                    DB::raw("(SELECT SUM(Descuento) FROM detalledocumentoventa WHERE CodigoVenta = dv.Codigo) AS descuentoTotal")
                )
                ->where('dv.Codigo', $venta)
                ->distinct()
                ->first();

            $detalleQuery = DB::table('detalledocumentoventa as ddv')
                ->join('producto as p', 'p.Codigo', '=', 'ddv.CodigoProducto')
                ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                ->select([
                    'ddv.Cantidad as cantidad',
                    'um.CodigoSUNAT AS unidad',
                    'ddv.Descripcion as descripcion',
                    DB::raw("(ddv.MontoTotal / ddv.Cantidad) as precioUnitario"),
                    'ddv.Descuento as descuento',
                    'ddv.MontoTotal as total'
                ])
                ->where('ddv.CodigoVenta', $venta)
                ->get();


            //log info
            Log::info('Consulta de Nota de Venta realizada correctamente.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'notaVentaPDF',
                'query' => ['venta' => $venta],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return response()->json(['data' => $query, 'productos' => $detalleQuery], 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar Nota de Venta.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'notaVentaPDF',
                'query' => ['venta' => $venta],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function consultarComprobantePorDatos(Request $request){

        $tipoDocumentoVenta = $request->input('tipoDocumentoVenta');
        $fecha = $request->input('fechaEmision');
        $serie = $request->input('serie');
        $numero = $request->input('numero');
        $documento = $request->input('numeroDocumentoCliente');

        try{
            $registro = DB::table('documentoventa as dv')
                ->leftJoin('personas as p', 'dv.CodigoPersona', '=', 'p.Codigo')
                ->leftJoin('clienteempresa as ce', 'dv.CodigoClienteEmpresa', '=', 'ce.Codigo')
                ->leftJoin('tipodocumentoventa as tdv', 'dv.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')
                ->where('dv.Serie', $serie)
                ->where('dv.Numero', intval($numero))
                ->whereDate('dv.Fecha', $fecha)
                ->where('dv.CodigoTipoDocumentoVenta', $tipoDocumentoVenta)
                ->where(function ($query) use ($documento) {
                    $query->where(function ($q) use ($documento) {
                            $q->where('tdv.CodigoSUNAT', '01')
                            ->where('ce.RUC', $documento);
                        })
                        ->orWhere(function ($q) use ($documento) {
                            $q->where('tdv.CodigoSUNAT', '<>', '01')
                            ->where('p.NumeroDocumento', $documento);
                        });
                })
                ->select('dv.Codigo')
                ->first();

            // Validar si existe
            if ($registro) {        
                return response()->json(['venta' => $registro->Codigo, 'encontrado' => true], 200);
            } else {
                Log::info('No se encontró el registro de comprobante por datos.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'consultarComprobantePorDatos',
                    'Data' => $request->all(),
                    'Mensaje' => 'No se encontró el registro'
                ]);
                return response()->json(['mensaje' => 'No se encontró el registro', 'encontrado' => false], 200);
            }

        }catch(\Exception $e){
            //log error
            Log::error('Error al consultar comprobante por datos.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarComprobantePorDatos',
                'Data' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Error al consultar comprobante por datos', 'encontrado' => false], 500);
        }
    }

    public function detalleComprobantePorDatos($venta){

        try{
            $venta = DB::table('documentoventa as dv')
             ->leftJoin('personas as p', 'dv.CodigoPersona', '=', 'p.Codigo')
             ->leftJoin('clienteempresa as ce', 'dv.CodigoClienteEmpresa', '=', 'ce.Codigo')
             ->leftJoin('tipodocumentoventa as tdv', 'dv.CodigoTipoDocumentoVenta', '=', 'tdv.Codigo')
             ->leftJoin('tipo_documentos as td', 'p.CodigoTipoDocumento', '=', 'td.Codigo')
             ->leftJoin('sedesrec as s', 'dv.CodigoSede', '=', 's.Codigo')
             ->leftJoin('pagodocumentoventa as pdv', 'dv.Codigo', '=', 'pdv.CodigoDocumentoVenta')
             ->leftJoin('pago as pag', 'pdv.CodigoPago', '=', 'pag.Codigo')
            ->leftJoin('mediopago as mp', 'pag.CodigoMedioPago', '=', 'mp.Codigo')
             ->where('dv.Codigo', $venta)
             ->select([
                 'dv.Codigo',
                 DB::raw("CONCAT(dv.Serie, ' - ', LPAD(dv.Numero, 5, '0')) as Serie"),
                 DB::raw("DATE(dv.Fecha) as Fecha"),
                 DB::raw("CASE 
                             WHEN ce.RUC IS NOT NULL THEN ce.RazonSocial
                             ELSE CONCAT(p.Nombres, ' ', p.Apellidos)
                         END AS Cliente"),
                 DB::raw("CASE 
                             WHEN ce.RUC IS NOT NULL THEN ce.RUC
                             ELSE CONCAT(td.Siglas, ' ', p.NumeroDocumento)
                         END AS Documento"),
                 DB::raw("CASE 
                             WHEN ce.RUC IS NOT NULL THEN ce.Direccion
                             ELSE p.Direccion
                         END AS Direccion"),
                 DB::raw("CONCAT(s.Nombre, ' - ', s.Direccion) as Sede"),
                 'dv.Vigente',
                 'dv.MontoTotal',
                 'tdv.Nombre as TipoDocumentoVenta',
                 'tdv.CodigoSUNAT as CodigoSUNAT',
                 'mp.Nombre as MedioPago',
                
             ])
             ->first();

                Log::info('Consulta de comprobante por datos realizada correctamente.', [
                    'Controlador' => 'VentaController',
                    'Metodo' => 'consultarComprobantePorDatos',
                    'Data' => $venta,
                    'Mensaje' => 'Registro encontrado',
                ]);
                
                return response()->json(['venta' => $venta, 'encontrado' => true], 200);


        }catch(\Exception $e){
            //log error
            Log::error('Error al consultar comprobante por datos.', [
                'Controlador' => 'VentaController',
                'Metodo' => 'consultarComprobantePorDatos',
                'Data' => $venta,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['error' => $e->getMessage(), 'mensaje' => 'Error al consultar comprobante por datos', 'encontrado' => false], 500);
        }
    }
}
