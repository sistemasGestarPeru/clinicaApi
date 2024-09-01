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

    private function getUploadConfig($pdfContent)
    {
        return [
            'fileContent' => $pdfContent,
            'projectId' => 'sitio-web-419317',
            'bucketName' => 'gestar-peru',
            'credentialsPath' => base_path('credentials.json')
        ];
    }

    // Método para subir el archivo a Google Cloud Storage
    private function uploadFile($config)
    {
        $storage = new StorageClient([
            'projectId' => $config['projectId'],
            'keyFilePath' => $config['credentialsPath']
        ]);

        $bucket = $storage->bucket($config['bucketName']);

        // Nombre del archivo remoto
        $remoteFileName = 'DocumentosVenta/' . uniqid() . '.pdf';

        // Subir el archivo a Google Cloud Storage usando el contenido binario directamente
        $bucket->upload($config['fileContent'], [
            'name' => $remoteFileName,
            'metadata' => [
                'contentType' => 'application/pdf'
            ]
        ]);

        return $remoteFileName;
    }


    //Metdo para consultar un archivo del bucket en Google Cloud Storage
    private function fileExists($fileName)
    {
        $storage = new StorageClient([
            'projectId' => 'sitio-web-419317',
            'keyFilePath' => base_path('credentials.json')
        ]);

        $bucket = $storage->bucket('gestar-peru');
        $object = $bucket->object($fileName);

        return $object->exists();
    }

    // Método para eliminar un archivo del bucket en Google Cloud Storage
    private function deleteFile($fileName)
    {
        $storage = new StorageClient([
            'projectId' => 'sitio-web-419317',
            'keyFilePath' => base_path('credentials.json')
        ]);

        $bucket = $storage->bucket('gestar-peru');

        $object = $bucket->object($fileName);
        $object->delete();
    }

    public function registrarVenta(Request $request)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d H:i:s');

        $detallesVentaData = $request->input('detalleVenta');
        $ventaData = $request->input('venta');
        $pagoData = $request->input('pago');

        if (!$detallesVentaData) {
            return response()->json(['error' => 'No se han enviado los detalles de la venta'], 400);
        }

        if (!$ventaData) {
            return response()->json(['error' => 'No se han enviado los datos de la venta'], 400);
        }

        if (!$pagoData) {
            return response()->json(['error' => 'No se han enviado los datos del pago'], 400);
        }

        if (isset($pagoData['CodigoCuentaBancaria']) && $pagoData['CodigoCuentaBancaria'] == 0) {
            $pagoData['CodigoCuentaBancaria'] = null;
        }

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

        $ventaData['EstadoFactura'] = 'M';
        $pagoData['Fecha'] = $fecha;
        $MedioPago = $pagoData['CodigoMedioPago'];

        $CodigoTipoDocumentoVenta = $ventaData['CodigoTipoDocumentoVenta'];
        $sede = $ventaData['CodigoSede'];


        DB::beginTransaction();
        try {

            $venta = Venta::create($ventaData);
            $codigoVenta = $venta->Codigo;

            foreach ($detallesVentaData as $detalle) {
                $detalle['CodigoVenta'] = $codigoVenta;
                DetalleVenta::create($detalle);
            }

            $pago = Pago::create($pagoData);
            $codigoPago = $pago->Codigo;

            PagoDocumentoVenta::create([
                'CodigoPago' => $codigoPago,
                'CodigoDocumentoVenta' => $codigoVenta,
                'Monto' => $pagoData['Monto'],
            ]);

            if ($pago['CodigoMedioPago'] == 1) {
                IngresoDinero::create([
                    'CodigoCaja' => $pagoData['CodigoCaja'],
                    'Fecha' => $fecha,
                    'Monto' => $pagoData['Monto'],
                    'Tipo' => 'I',
                ]);
            }

            LocalDocumentoVenta::create([
                'CodigoSede' => $sede,
                'Serie' => $ventaData['Serie'],
                'CodigoTipoDocumentoVenta' => $CodigoTipoDocumentoVenta,
                'Vigente' => 1
            ]);

            LocalMedioPago::create([
                'CodigoSede' => $sede,
                'CodigoMedioPago' => $MedioPago,
                'Vigente' => 1
            ]);

            //$url = $this->generarPDF(); //asignar a una variable de la tabla DetalleVenta

            DB::commit();
            return response()->json(['message' => 'Venta registrada correctamente.'], 201);
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


    public function buscarVenta(Request $request)
    {
        try {
            $fecha = $request->input('fecha');
            $codigoSede = $request->input('codigoSede');

            $venta = DB::table('clinica_db.documentoventa')
                ->where(DB::raw("DATE(Fecha)"), $fecha)
                ->where('CodigoSede', $codigoSede)
                ->where('Vigente', 1)
                ->select('Codigo', 'Serie', 'Numero', 'CodigoTipoDocumentoVenta as TipoDoc', DB::raw("DATE(Fecha) as Fecha"))
                ->get();
            return response()->json($venta);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'msg' => 'Error al buscar Venta'], 500);
        }
    }

    public function consultaNumDocumentoVenta(Request $request)
    {
        $sede = $request->input('sede');
        $tipoDocumento = $request->input('tipoDocumento');

        try {
            // Obtener el último documento de venta
            $documentoVenta = DB::table('clinica_db.documentoventa')
                ->where('CodigoTipoDocumentoVenta', $tipoDocumento)
                ->where('CodigoSede', $sede)
                ->orderBy('Codigo', 'desc')
                ->first(['Serie', 'Numero']);

            if ($documentoVenta) {
                // Incrementar el número y ajustar la serie si es necesario
                $nuevoNumero = $documentoVenta->Numero + 1;
                $nuevaSerie = $documentoVenta->Serie;

                if ($nuevoNumero > 9999) {
                    $nuevoNumero = 1;
                    $nuevaSerie = $documentoVenta->Serie + 1;
                }
            } else {
                // Si no hay registros previos, inicializar la serie y número
                $nuevoNumero = 1;
                $nuevaSerie = 1;
            }

            // Retornar la nueva serie y número
            return response()->json([
                'Serie' => $nuevaSerie,
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

            $pago = Pago::find($pagoDocVenta->CodigoPago);

            if (!$pago) {
                return response()->json(['error' => 'Pago no encontrado.'], 404);
            }
            $pago->Vigente = 0;
            $pago->save();

            DB::commit();

            return response()->json(['message' => 'Venta anulada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generarPDF()
    {
        // Crear una nueva instancia de TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Establecer información del documento
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Tu Empresa');
        $pdf->SetTitle('Boleta de Venta Electrónica');
        $pdf->SetSubject('Boleta de Venta');
        $pdf->SetKeywords('TCPDF, PDF, boleta, venta, electronica');

        // Establecer márgenes
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);

        // Deshabilitar el encabezado y pie de página automáticos
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Agregar una página
        $pdf->AddPage();

        // Configurar la fuente
        $pdf->SetFont('helvetica', '', 10);

        // Agregar encabezado
        $pdf->Cell(0, 10, 'BOLETA DE VENTA ELECTRONICA', 0, 1, 'C');

        // Información del Cliente
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 10, 'DNSYSTEMS', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 6, 'OBLITAS MEZA SUSANA', 0, 1, 'L');
        $pdf->Cell(0, 6, 'CAL. SANJOSE 375 INT. 2 URB. CERCADO DE CHICLAYO', 0, 1, 'L');
        $pdf->Cell(0, 6, 'CHICLAYO - CHICLAYO - LAMBAYEQUE', 0, 1, 'L');
        $pdf->Ln(5);

        // Fecha y otros detalles
        $pdf->Cell(40, 6, 'Fecha de Emision:', 0, 0, 'L');
        $pdf->Cell(40, 6, '10/10/2022', 0, 1, 'L');

        $pdf->Cell(40, 6, 'Señor(es):', 0, 0, 'L');
        $pdf->Cell(40, 6, 'DEMETRIO REYES INOÑAN', 0, 1, 'L');

        $pdf->Cell(40, 6, 'DNI:', 0, 0, 'L');
        $pdf->Cell(40, 6, '75193860', 0, 1, 'L');
        $pdf->Ln(10);

        // Encabezado de tabla
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'Cantidad', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Descripcion', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Valor Unitario', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Importe de Venta', 1, 0, 'C');
        $pdf->Cell(30, 6, 'ICBPER', 1, 1, 'C');

        // Detalles del Producto
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(20, 6, '1.00', 1, 0, 'C');
        $pdf->Cell(40, 6, 'LAPTOP LENOVO IDEAPAD', 1, 0, 'L');
        $pdf->Cell(40, 6, '2927.9661', 1, 0, 'R');
        $pdf->Cell(30, 6, '3454.9998', 1, 0, 'R');
        $pdf->Cell(30, 6, '0.00', 1, 1, 'R');

        // Otros totales y cargos
        $pdf->Ln(10);
        $pdf->Cell(40, 6, 'Op. Gravada:', 0, 0, 'L');
        $pdf->Cell(40, 6, 'S/ 2927.97', 0, 1, 'R');

        $pdf->Cell(40, 6, 'IGV:', 0, 0, 'L');
        $pdf->Cell(40, 6, 'S/ 527.03', 0, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 6, 'Importe Total:', 0, 0, 'L');
        $pdf->Cell(40, 6, 'S/ 3455.00', 0, 1, 'R');
        // Detalles adicionales del PDF...





        /************************************ NO MOVER NADA DESDE AQUI ************************************/
        // Guardar el PDF en una variable como cadena binaria
        $pdfContent = $pdf->Output('documento_venta.pdf', 'S'); // 'S' para devolverlo como cadena

        //PARA VER EL PDF EN EL POSTMAN  --- ELIMINAR ESTE RETURN LUEGO
        return Response::make($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="boleta_de_venta.pdf"'
        ]);

        //DESCOMENTAR LUEGO
        // // Configuración para subir el archivo a Google Cloud Storage
        // $uploadConfig = $this->getUploadConfig($pdfContent);

        // // Subir el archivo a Google Cloud Storage
        // $remoteFileName = $this->uploadFile($uploadConfig);

        // // Crear respuesta HTTP con el PDF
        // return $remoteFileName;
    }
}
