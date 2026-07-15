<?php

namespace App\Http\Controllers\API\Recaudacion\FacturacionElectronica;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\FacturacionElectronica\EnvioFacturacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacturacionElectronicaController extends Controller
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


    public function detallesFacturacionElectronica($ventaData, $detallesVenta)
    {
        try {

            // Obtener datos del emisor 
            $datosEmisor = DB::table('sedesrec as s')
                ->join('empresas as e', 's.CodigoEmpresa', '=', 'e.Codigo')
                ->where('s.Codigo', $ventaData->CodigoSede)
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
                    'CodigoSede' => $ventaData->CodigoSede,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                ]);

                return response()->json(['error' => 'Datos del emisor no encontrados.'], 404);
            }

            // Obtener tipo de documento venta
            $tipoDocumentoVenta = DB::table('tipodocumentoventa as tdv')
                ->where('tdv.Codigo', $ventaData->CodigoTipoDocumentoVenta)
                ->select('tdv.CodigoSUNAT')
                ->first();

            // Obtener datos del cliente
            if ($ventaData->CodigoPersona != null) {
                $cliente = DB::table('personas as p')
                    ->join('tipo_documentos as td', 'p.CodigoTipoDocumento', '=', 'td.Codigo')
                    ->where('p.Codigo', $ventaData->CodigoPersona)
                    ->select(
                        'p.NumeroDocumento',
                        DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as Nombres"),
                        'td.CodigoSUNAT',
                        'p.Direccion',
                        'p.Correo'
                    )
                    ->first();
            } elseif ($ventaData->CodigoClienteEmpresa != null) {
                $cliente = DB::table('clienteempresa')
                    ->where('Codigo', $ventaData->CodigoClienteEmpresa)
                    ->select(
                        'RUC as NumeroDocumento',
                        'RazonSocial as Nombres',
                        DB::raw("6 as CodigoSUNAT"),
                        'Direccion',
                        DB::raw("'' as Correo")
                    )
                    ->first();
            }

            // Parsear fecha y hora
            $fechaHora = Carbon::parse($ventaData->Fecha);
            $fechaEmision = $fechaHora->format('Y-m-d');
            $horaEmision = $fechaHora->format('H:i:s');

            // Procesar detalles
            $detallesFormateados = [];

            foreach ($detallesVenta as $detalle) {
                $datosProductoSede = DB::table('sedeproducto as sp')
                    ->join('producto as p', 'sp.CodigoProducto', '=', 'p.Codigo')
                    ->join('unidadmedida as um', 'p.CodigoUnidadMedida', '=', 'um.Codigo')
                    ->join('tipogravado as tg', 'sp.CodigoTipoGravado', '=', 'tg.Codigo')
                    ->where('p.Codigo', $detalle->CodigoProducto)
                    ->where('sp.CodigoSede', $ventaData->CodigoSede)
                    ->select([
                        'um.CodigoSUNAT as unidadMedida',
                        'tg.CodigoSUNAT as tipoGravado'
                    ])
                    ->first();

                $detallesFormateados[] = [
                    'num_lin_item' => $detalle->Numero,
                    'cod_unid_item' => $datosProductoSede->unidadMedida,
                    'cant_unid_item' => $detalle->Cantidad ?? 0,
                    'val_vta_item' => round(abs(($detalle->MontoTotal ?? 0) - ($detalle->MontoIGV ?? 0)), 4),
                    'cod_tip_afect_igv_item' => $datosProductoSede->tipoGravado,
                    'prc_vta_unit_item' => round(abs(($detalle->MontoTotal ?? 0) / max($detalle->Cantidad ?? 1, 1)), 4),
                    'mnt_dscto_item' => round(abs($detalle->Descuento ?? 0), 4),
                    'mnt_igv_item' => round(abs($detalle->MontoIGV ?? 0), 4),
                    'txt_descr_item' => $detalle->Descripcion ?? 'Producto sin descripción',
                    'val_unit_item' => round(abs((($detalle->MontoTotal ?? 0) - ($detalle->MontoIGV ?? 0)) / max($detalle->Cantidad ?? 1, 1)), 4),
                    'importe_total_item' => round(abs($detalle->MontoTotal ?? 0), 4),
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
                    ->where('nc.CodigoDocumentoReferencia', $ventaData->CodigoDocumentoReferencia)
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
                    'txt_serie' => $ventaData->Serie ?? '',
                    'txt_correlativo' => $ventaData->Numero ?? '',
                    'cod_tip_cpe' => $tipoDocumentoVenta->CodigoSUNAT,
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
                    'mnt_tot_gravadas'     => round(abs($ventaData->TotalGravado ?? 0), 4),
                    'mnt_tot_inafectas'    => round(abs($ventaData->TotalInafecto ?? 0), 4),
                    'mnt_tot_exoneradas'   => round(abs($ventaData->TotalExonerado ?? 0), 4),
                    'mnt_tot_gratuitas'    => round(abs($ventaData->TotalGratis ?? 0), 4),
                    'mnt_tot_desc_global'  => round(abs($ventaData->TotalDescuento ?? 0), 4),
                    'mnt_tot_igv'          => round(abs($ventaData->IGVTotal ?? 0), 4),
                    'mnt_tot'              => round(abs($ventaData->MontoTotal ?? 0), 4),
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
                    'cod_tip_nc_nd_ref' => $debito_credito_nota->DocumentoCodigo, // Código del tipo de documento de referencia
                    'txt_serie_ref' => $debito_credito_nota->Serie, // Serie del comprobante de referencia
                    'txt_correlativo_cpe_ref' => $debito_credito_nota->Numero, // Correlativo del comprobante de referencia
                    'fec_emis_ref' => $debito_credito_nota->Fecha, // Fecha de emisión del comprobante de referencia
                    'cod_cpe_ref' => $debito_credito_nota->MotivoCodigo, // Código SUNAT del comprobante de referencia
                    'txt_sustento' => $debito_credito_nota->Motivo // Sustento de la nota
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
                    'URL' => env('PSE_API_URL'),
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
                    'JSON' => $facturacionElectronica ?? 'Error JSON',
                    'Estado' => 'N',
                    'URL' => env('PSE_API_URL')
                ];
            }

        } catch (\Exception $e) {

            Log::error('Error al generar el JSON de facturación electrónica.', [
                'Controlador' => 'FacturacionElectronicaController',
                'Metodo' => 'detallesFacturacionElectronica',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
            ]);

            return [
                'success' => false,
                'Mensaje' => 'Error Interno',
                'JSON' => $facturacionElectronica ?? 'Error JSON',
                'Estado' => 'N',
                'error' => $e->getMessage(),
                'URL' => env('PSE_API_URL')
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
                'fec_gener_baja' => $anulacionData->Fecha,
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
                    'URL' => env('PSE_API_URL'),
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
                    'JSON' => $anulacionJSON ?? 'Error JSON',
                    'Estado' => 'N',
                    'URL' => env('PSE_API_URL'),
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

            return [
                'success' => false,
                'Mensaje' => 'Error Interno',
                'JSON' => $anulacionJSON ?? 'Error JSON',
                'Estado' => 'N',
                'error' => $e->getMessage(),
                'URL' => env('PSE_API_URL')
            ];
        }
    }

    public function registrarEnvio(Request $request)
    {

        $fechaActual = date('Y-m-d H:i:s');

        $result = DB::table('enviofacturacion as e')
            ->select('e.Tipo', 'e.JSON', 'e.URL', 'e.CodigoDocumentoVenta', 'e.CodigoAnulacion', 'e.CodigoSede')
            ->where('e.Codigo', $request->Codigo)
            ->first(); // O ->get() si esperas múltiples resultados


        //validar si $result->CodigoDocumentoVenta existe y es diferente de null y 0
        if ($result->CodigoDocumentoVenta) {
            //encontrar la venta
            $venta = DB::table('documentoventa')
                ->where('Codigo', $result->CodigoDocumentoVenta)
                ->first();

            // encontrar los detalles de la venta
            $detallesVenta = DB::table('detalledocumentoventa')
                ->where('CodigoVenta', $result->CodigoDocumentoVenta)
                ->get();

            // enviar los detalles de la venta a la funcion detallesFacturacionElectronica
            $respuesta = $this->detallesFacturacionElectronica($venta, $detallesVenta);
        }

        if($result->CodigoAnulacion){
            //encontrar la anulacion
            $anulacionData = DB::table('anulacion')
                ->where('Codigo', $result->CodigoAnulacion)
                ->first();


           $respuesta = $this->anularFacturacionElectronica($anulacionData->CodigoDocumentoVenta, $anulacionData, $result->CodigoAnulacion);
        }


        $dataEnvio['Tipo'] = $result->Tipo;
        $dataEnvio['JSON'] = is_array($respuesta['JSON']) ? json_encode($respuesta['JSON']) : $respuesta['JSON'];
        $dataEnvio['URL'] = $respuesta['URL'];
        $dataEnvio['Fecha'] = $fechaActual;
        $dataEnvio['CodigoTrabajador'] = $request->CodigoTrabajador;
        $dataEnvio['Estado'] = $respuesta['Estado'];
        $dataEnvio['Mensaje'] = $respuesta['Mensaje'];
        $dataEnvio['CodigoDocumentoVenta'] = $result->CodigoDocumentoVenta;
        $dataEnvio['CodigoAnulacion'] = $result->CodigoAnulacion;
        $dataEnvio['CodigoSede'] = $result->CodigoSede;

        try {
            EnvioFacturacion::create($dataEnvio);

            //log info
            Log::info('Registro de Envio Facturacion Electronica', [
                'Controlador' => 'FacturacionElectronicaController',
                'Metodo' => 'registrarEnvio',
                'Codigo' => $result->CodigoDocumentoVenta ?? $result->CodigoAnulacion,
                'Tipo' => $result->Tipo,
                'Estado' => $respuesta['Estado'],
                'Mensaje' => $respuesta['Mensaje'],
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Envio de factura electronica registrado correctamente.',
                'facturacion' => [
                    'success' => $respuesta['success'],
                    'Mensaje' => $respuesta['Mensaje']
                ]
            ], 201);

        } catch (\Exception $e) {

            //log error
            Log::error('Error al registrar el envio de factura electronica', [
                'Controlador' => 'FacturacionElectronicaController',
                'Metodo' => 'registrarEnvio',
                'Codigo' => $result->CodigoDocumentoVenta ?? $result->CodigoAnulacion,
                'Tipo' => $result->Tipo,
                'Estado' => 'N',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Error al registrar el envio de la factura electronica.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function listarEnviosFallidos(Request $request)
    {

        try {

            $subquery = DB::table('enviofacturacion as e')
                ->leftJoin('documentoventa as dv', 'e.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('anulacion as a', 'e.CodigoAnulacion', '=', 'a.Codigo')
                ->leftJoin('documentoventa as da', 'a.CodigoDocumentoVenta', '=', 'da.Codigo')
                ->select([
                    DB::raw('MAX(e.Codigo) as Codigo'),
                    'e.Tipo',
                    DB::raw("
                        CASE
                            WHEN e.CodigoDocumentoVenta IS NULL THEN CONCAT(da.Serie, '-', da.Numero)
                            WHEN e.CodigoAnulacion IS NULL THEN CONCAT(dv.Serie, '-', dv.Numero)
                            ELSE 'Desconocido'
                        END AS Documento
                    "),
                    DB::raw("
                        MAX(CASE WHEN e.Estado = 'A' THEN e.Codigo ELSE NULL END) AS CodigoEstadoA
                    ")
                ])
                ->groupBy(
                    'e.Tipo',
                    DB::raw("
                        CASE
                            WHEN e.CodigoDocumentoVenta IS NULL THEN CONCAT(da.Serie, '-', da.Numero)
                            WHEN e.CodigoAnulacion IS NULL THEN CONCAT(dv.Serie, '-', dv.Numero)
                            ELSE 'Desconocido'
                        END
                    ")
                )
                ->havingRaw('MAX(CASE WHEN e.Estado = "A" THEN e.Codigo ELSE NULL END) IS NULL');


            // Consulta principal
            $result = DB::table(DB::raw("({$subquery->toSql()}) as ultimos"))
                ->mergeBindings($subquery)
                ->join('enviofacturacion as e', 'e.Codigo', '=', 'ultimos.Codigo')
                ->select('e.Codigo', 'e.Tipo', 'ultimos.Documento', 'e.Fecha', 'e.Mensaje')
                ->where('e.CodigoSede', $request->Sede)

                ->when($request->Tipo, function ($query) use ($request) {
                    return $query->where('e.Tipo', $request->Tipo);
                })

                ->when($request->Fecha, function ($query) use ($request) {
                    return $query->whereDate('e.Fecha', '=', $request->Fecha);
                })

                ->when($request->Referencia, function ($query) use ($request) {
                    return $query->where('ultimos.Documento', 'like', $request->Referencia . '%');
                })

                ->orderByDesc('e.Codigo')
                ->get();

            Log::info('Listar Envios Fallidos', [
                'Controlador' => 'FacturacionElectronicaController',
                'Metodo' => 'listarEnviosFallidos',
                'Cantidad' => $result->count(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($result, 200);
        } catch (\Exception $e) {

            //log error
            Log::error('Error al listar los envios fallidos', [
                'Controlador' => 'FacturacionElectronicaController',
                'Metodo' => 'listarEnviosFallidos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Error al listar los envios fallidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
