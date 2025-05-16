<?php

namespace App\Http\Controllers\API\Recaudacion\FacturacionElectronica;

use App\Http\Controllers\Controller;
use App\Models\Recaudacion\FacturacionElectronica\EnvioFacturacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

    public function envioFacturacionElectronica($JSON, $URL, $TokenPSE){

        $jsonArray = json_decode($JSON, true); // <- Convierte el string JSON en array

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'Mensaje' => 'JSON malformado: ' . json_last_error_msg(),
                'JSON' => $JSON,
                'Estado' => 'N',
            ];
        }

            // Enviar el JSON a la API de facturación electrónica
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $TokenPSE
            ])->post($URL, $jsonArray);
            

            if ($response->successful()) {
                $data = $response->json();
                $mensaje = $data['Mensaje'] ?? 'Mensaje no disponible';
                $resultado = $data['Resultado'] ?? false;
                
                return [
                    'success' => $resultado,
                    'Mensaje' => $mensaje,
                    'JSON' => $jsonArray,
                    'Estado' => $resultado ? 'A' : 'R',
                ];

            } else {
                $status = $response->status();
                $mensajeError = $status === 401
                    ? '401 - No autorizado'
                    : '500 - Error interno del servidor';
            
                return [
                    'success' => false,
                    'Mensaje' => $mensajeError,
                    'JSON' => $jsonArray,
                    'Estado' => 'N',
                ];
            }
    }

    public function registrarEnvio(Request $request){

        $fechaActual = date('Y-m-d H:i:s');

        // $tokenPSE = 'WsC0nexGESTLAB@:ILhCvhfykj8lnMAJGGZBog==';
        
        // DB::table('empresas') //CAMBIAR esta en DURO
        //     ->where('Codigo', 1)
        //     ->value('TokenPSE');         

        $result = DB::table('enviofacturacion as e')
            ->select('e.Tipo', 'e.JSON', 'e.URL', 'e.CodigoDocumentoVenta', 'e.CodigoAnulacion', 'e.CodigoSede')
            ->where('e.Codigo', $request->Codigo)
            ->first(); // O ->get() si esperas múltiples resultados

        // Obtener datos del emisor 
        $tokenPSE = DB::table('sedesrec as s')
        ->join('empresas as e', 's.CodigoEmpresa', '=', 'e.Codigo')
        ->where('s.Codigo', $result->CodigoSede)
        ->value('e.TokenPSE');


        $data = $this->envioFacturacionElectronica($result->JSON, $result->URL, $tokenPSE);

        $dataEnvio['Tipo'] = $result->Tipo;
        $dataEnvio['JSON'] = $result->JSON;
        $dataEnvio['URL'] = $result->URL;
        $dataEnvio['Fecha'] = $fechaActual;
        $dataEnvio['CodigoTrabajador'] = $request->CodigoTrabajador;
        $dataEnvio['Estado'] = $data['Estado'];
        $dataEnvio['Mensaje'] = $data['Mensaje'];
        $dataEnvio['CodigoDocumentoVenta'] = $result->CodigoDocumentoVenta;
        $dataEnvio['CodigoAnulacion'] = $result->CodigoAnulacion;  
        $dataEnvio['CodigoSede'] = $result->CodigoSede;  
        try{
            EnvioFacturacion::create($dataEnvio);
            return response()->json([
                'message' => 'Envio de factura electronica registrado correctamente.',
                'facturacion' => [
                    'success' => $data['success'],
                    'Mensaje' => $data['Mensaje'],
                    'JSON' => $data['JSON'],
                    'URL' => $result->URL,
                    'TokenPSE' => $tokenPSE,
                ]
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al registrar el envio de la factura electronica.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }


    public function listarEnviosFallidos(Request $request){

        try{

            $subquery = DB::table('enviofacturacion as e')
            ->leftJoin('documentoventa as dv', 'e.CodigoDocumentoVenta', '=', 'dv.Codigo')
            ->leftJoin('anulacion as a', 'e.CodigoAnulacion', '=', 'a.Codigo')
            ->leftJoin('documentoventa as da', 'a.CodigoDocumentoVenta', '=', 'da.Codigo')
            ->whereIn('e.Estado', ['N', 'R'])
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
            ])
            ->groupBy('e.Tipo', DB::raw("
                CASE
                    WHEN e.CodigoDocumentoVenta IS NULL THEN CONCAT(da.Serie, '-', da.Numero)
                    WHEN e.CodigoAnulacion IS NULL THEN CONCAT(dv.Serie, '-', dv.Numero)
                    ELSE 'Desconocido'
                END
            "));
        
            // Consulta principal uniendo con la tabla original para obtener los demás campos
            $result = DB::table(DB::raw("({$subquery->toSql()}) as ultimos"))
                ->mergeBindings($subquery) // Importante para evitar error de bindings
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

                ->orderBy('e.Codigo', 'desc')
                ->get();

            return response()->json($result, 200);

        }catch(\Exception $e){
            return response()->json([
                'message' => 'Error al listar los envios fallidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
