<?php

namespace App\Http\Controllers\API\ControladoresGenerales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ControladorGeneralController extends Controller
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

    public function listarApp($codigoTrabajador)
    {
        try {
            $resultado = DB::table('usuario_perfil as up')
                ->join('rol as r', 'up.CodigoRol', '=', 'r.Codigo')
                ->join('aplicacion as a', 'r.CodigoAplicacion', '=', 'a.Codigo')
                ->where('up.CodigoPersona', $codigoTrabajador)
                ->where('a.Vigente', 1)
                ->distinct()
                ->select('a.Nombre', 'a.URL')
                ->get();

            Log::info('Apps encontradas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarApp',
                'Trabajador' => $codigoTrabajador,
                'Cantidad' => count($resultado),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($resultado, 200);
        } catch (\Exception $e) {

            Log::error('Error al listar Apps', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'buscarProducto',
                'Trabajador' => $codigoTrabajador,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function ConsultaEmpresasTrab($codigoTrabajador)
    {
        try {
            $empresas = DB::table('contrato_laborals as cl')
                ->join('empresas as e', 'e.Codigo', '=', 'cl.CodigoEmpresa')
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->where('cl.CodigoTrabajador', $codigoTrabajador)
                ->get();

            Log::info('Empresas encontradas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'ConsultaEmpresasTrab',
                'Trabajador' => $codigoTrabajador,
                'Cantidad' => count($empresas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($empresas, 200);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTipoDocIdentidad()
    {

        try {
            $documentos = DB::table('tipo_documentos')
                ->where('Vigente', 1)
                ->select('Codigo as Codigo', 'Siglas as Nombre', 'CodigoSUNAT')
                ->get();

            Log::info('Tipo de documentos de identidad listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTipoDocIdentidad',
                'Cantidad' => count($documentos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($documentos, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar tipo de documentos de identidad', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTipoDocIdentidad',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    //Listar Combo Empresas
    public function listarEmpresas()
    {
        try {
            $empresas = DB::table('empresas')
                ->where('Vigente', 1)
                ->select('Codigo as id', 'Nombre as nombre')
                ->get();

            Log::info('Empresas listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarEmpresas',
                'Cantidad' => count($empresas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($empresas, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar empresas', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarEmpresas',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }
    //Listar Combo Sedes - Empresas
    public function listarSedesEmpresas($codigoEmpresa)
    {
        try {
            $sedes = DB::table('sedesrec')
                ->where('CodigoEmpresa', $codigoEmpresa)
                ->select('Codigo as id', 'Nombre as nombre')
                ->get();

            Log::info('Sedes listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarSedesEmpresas',
                'Empresa' => $codigoEmpresa,
                'Cantidad' => count($sedes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($sedes, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar sedes de la empresa', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarSedesEmpresas',
                'Empresa' => $codigoEmpresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cboSedesDisponibles($codigoEmpresa, $codigoTrabajador)
    {
        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d');
        try {
            $sedes = DB::table('sedesrec as s')
                ->select('s.Codigo as id', 's.Nombre as nombre')
                ->leftJoin('asignacion_sedes as ass', function ($join) use ($codigoTrabajador, $fechaActual) {
                    $join->on('s.Codigo', '=', 'ass.CodigoSede')
                        ->where('ass.Codigo', '=', DB::raw("(SELECT MAX(Codigo)
                                                          FROM asignacion_sedes
                                                          WHERE CodigoSede = s.Codigo
                                                          AND CodigoTrabajador = {$codigoTrabajador}
                                                          AND Vigente = 1)"))
                        ->where(function ($query) use ($fechaActual) {
                            $query->where('ass.FechaFin', '>', $fechaActual)
                                ->orWhereNull('ass.FechaFin');
                        });
                })
                ->where('s.CodigoEmpresa', $codigoEmpresa)
                ->where('s.Vigente', 1)
                ->whereNull('ass.Codigo')
                ->get();

            Log::info('Sedes Disponibles listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'cboSedesDisponibles',
                'Empresa' => $codigoEmpresa,
                'Cantidad' => count($sedes),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($sedes, 200);
        } catch (\Exception $e) {

            Log::error('Error al buscar producto', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'cboSedesDisponibles',
                'Empresa' => $codigoEmpresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cboEmpresasDisponibles($codigoTrabajador)
    {
        date_default_timezone_set('America/Lima');
        $fechaActual = date('Y-m-d');

        try {
            $empresas = DB::table('empresas as e')
                ->select('e.Codigo as id', 'e.Nombre as nombre')
                ->leftJoin('contrato_laborals as cl', function ($join) use ($fechaActual, $codigoTrabajador) {
                    $join->on('e.Codigo', '=', 'cl.CodigoEmpresa')
                        ->where('cl.Codigo', '=', DB::raw("(SELECT MAX(Codigo)
                                                      FROM contrato_laborals
                                                      WHERE CodigoEmpresa = e.Codigo
                                                      AND CodigoTrabajador = {$codigoTrabajador}
                                                      AND Vigente = 1)"))
                        ->where(function ($query) use ($fechaActual) {
                            $query->where('cl.FechaFin', '>', $fechaActual)
                                ->orWhereNull('cl.FechaFin');
                        });
                })
                ->where('e.Vigente', 1)
                ->whereNull('cl.Codigo')
                ->get();
            Log::info('Empresas Disponibles listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'cboEmpresasDisponibles',
                'Trabajador' => $codigoTrabajador,
                'Cantidad' => count($empresas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($empresas);
        } catch (\Exception $e) {
            Log::error('Error al listar empresas disponibles', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'cboEmpresasDisponibles',
                'Trabajador' => $codigoTrabajador,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    // public function listarDepartamentos($sede)
    // {
    //     try {
    //         $departamentos = DB::table('sedesrec as s')
    //             ->join('departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
    //             ->where('s.Codigo', $sede)
    //             ->where('s.Vigente', 1)
    //             ->where('d.Vigente', 1)
    //             ->select('s.CodigoDepartamento as CodigoDepartamento')
    //             ->first();

    //         return response()->json($departamentos);
    //     } catch (\Exception $e) {
    //         return response()->json('Error en la consulta: ' . $e->getMessage());
    //     }
    // }

    public function listarTiposDocVenta($sede, $tipo)
    {

        if (!$tipo) {
            $tipo = 'V';
        }

        try {

            $docVentas = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->select('tdv.Codigo', 'tdv.Nombre', 'tdv.CodigoSUNAT')
                ->where('ldv.CodigoSede', $sede)
                ->where('tdv.Vigente', 1)
                ->where('ldv.Vigente', 1)
                ->when($tipo !== 'T', function ($query) use ($tipo) {
                    return $query->where('tdv.Tipo', $tipo);
                })
                ->distinct()
                ->get();

            Log::info('Tipos de documentos de venta listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTiposDocVenta',
                'Sede' => $sede,
                'Tipo' => $tipo,
                'Cantidad' => count($docVentas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($docVentas);
        } catch (\Exception $e) {
            Log::error('Error al listar tipos de documentos de venta', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTiposDocVenta',
                'Sede' => $sede,
                'Tipo' => $tipo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTiposDocVentaDevolucion($sede, $cod)
    {

        try {

            $codigoSunat = DB::table('tipodocumentoventa')
                ->where('Codigo', $cod)
                ->value('CodigoSUNAT');

            $docVentas = DB::table('localdocumentoventa as ldv')
                ->join('tipodocumentoventa as tdv', 'tdv.Codigo', '=', 'ldv.CodigoTipoDocumentoVenta')
                ->where('ldv.CodigoSede', $sede)
                ->where('tdv.Vigente', 1)
                ->where('ldv.Vigente', 1)
                ->where('tdv.Tipo', 'D')
                ->where(function ($query) use ($codigoSunat) {
                    if (is_null($codigoSunat)) {
                        $query->whereNull('tdv.CodigoSUNAT');
                    } else {
                        $query->where('tdv.CodigoSUNAT', $codigoSunat);
                    }
                })
                ->select('tdv.Codigo', 'tdv.Nombre', 'tdv.CodigoSUNAT')
                ->distinct()
                ->get();

            Log::info('Tipos de documentos de venta para devoluciones listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTiposDocVentaDevolucion',
                'Sede' => $sede,
                'CodigoSunat' => $codigoSunat,
                'Cantidad' => count($docVentas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($docVentas);
        } catch (\Exception $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarSistemaPension()
    {
        try {
            $sistemaPension = DB::table('sistemapensiones as sp')
                ->where('sp.Vigente', 1)
                ->select('sp.Codigo', 'sp.Siglas as Nombre')
                ->get();

            Log::info('Sistemas de pensión listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarSistemaPension',
                'Cantidad' => count($sistemaPension),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($sistemaPension);
        } catch (\Exception $e) {
            Log::error('Error al listar sistemas de pensión', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarSistemaPension',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMedioPago($sede)
    {
        try {
            $medioPago = DB::table('localmediopago as lmp')
                ->join('sedesrec as s', 's.Codigo', '=', 'lmp.CodigoSede')
                ->join('mediopago as mp', 'mp.Codigo', '=', 'lmp.CodigoMedioPago')
                ->select('mp.Codigo', 'mp.Nombre', 'mp.CodigoSUNAT')
                ->where('lmp.CodigoSede', $sede)
                ->where('mp.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('lmp.Vigente', 1)
                ->get();
            Log::info('Medios de pago listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMedioPago',
                'Sede' => $sede,
                'Cantidad' => count($medioPago),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($medioPago);
        } catch (\Exception $e) {
            Log::error('Error al listar medios de pago', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMedioPago',
                'Sede' => $sede,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarBilleterasDigitalesEmpresa($empresa)
    {
        try {
            $result = DB::table('billeteradigital as bd')
                ->join('entidadbilleteradigital as ebd', 'ebd.Codigo', '=', 'bd.Codigo')
                ->where('bd.CodigoEmpresa', $empresa)
                ->where('bd.Vigente', 1)
                ->where('ebd.Vigente', 1)
                ->select('bd.Codigo', DB::raw("CONCAT(ebd.Nombre, ' - ', bd.Numero) AS Nombre"))
                ->get();

            Log::info('Billeteras digitales listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarBilleterasDigitalesEmpresa',
                'Empresa' => $empresa,
                'Cantidad' => count($result),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($result);
        } catch (\Exception $e) {

            Log::error('Error al listar billeteras digitales', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarBilleterasDigitalesEmpresa',
                'Empresa' => $empresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarCuentasBancariasEmpresa($empresa)
    {
        try {
            $result = DB::table('cuentabancaria as cb')
                ->join('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria')
                ->where('cb.CodigoEmpresa', $empresa)
                ->where('cb.Vigente', 1)
                ->where('eb.Vigente', 1)
                ->where('cb.Detraccion', 0)
                ->select('cb.Codigo as Codigo', 'eb.Siglas', 'cb.Numero')
                ->get();

            Log::info('Cuentas bancarias listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarCuentasBancariasEmpresa',
                'Empresa' => $empresa,
                'Cantidad' => count($result),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($result);
        } catch (\Exception $e) {

            Log::error('Error al listar cuentas bancarias', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarCuentasBancariasEmpresa',
                'Empresa' => $empresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMotivosAnulacion()
    {
        try {
            $result = DB::table('motivoanulacion')
                ->where('Vigente', 1)
                ->select('Codigo as Codigo', 'Nombre as Nombre', 'Descripcion as Descripcion')
                ->get();
            Log::info('Motivos de anulación listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivosAnulacion',
                'Cantidad' => count($result),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error al listar motivos de anulación', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivosAnulacion',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function cuentaDetraccion($empresa)
    {

        try {

            $resultado = DB::table('cuentabancaria as cb')
                ->select('cb.Codigo', 'eb.Nombre', 'eb.Siglas', 'cb.Numero', 'cb.CCI')
                ->join('empresas as e', 'e.Codigo', '=', 'cb.CodigoEmpresa') // Relación con empresas
                ->join('entidadbancaria as eb', 'eb.Codigo', '=', 'cb.CodigoEntidadBancaria') // Relación con entidad bancaria
                ->where('cb.Detraccion', 1) // Filtro por Detracción
                ->where('cb.Vigente', 1) // Filtro por Vigencia de cuenta bancaria
                ->where('e.Codigo', $empresa) // Filtro por Código de Empresa
                ->where('e.Vigente', 1) // Filtro por Vigencia de Empresa
                ->where('eb.Vigente', 1) // Filtro por Vigencia de Entidad Bancaria
                ->first();

            if (!$resultado) {
                Log::info('No se encontraron cuentas de detracción para la empresa', [
                    'Controlador' => 'ControladorGeneralController',
                    'Metodo' => 'cuentaDetraccion',
                    'Empresa' => $empresa,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json('No se encontraron cuentas de detracción para la empresa', 404);
            }
            Log::info('Cuenta de detracción encontrada correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'cuentaDetraccion',
                'Empresa' => $empresa,
                'Cuenta' => $resultado->Codigo,
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al obtener cuenta de detracción', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'cuentaDetraccion',
                'Empresa' => $empresa,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }


    public function listarMotivoPagoServicio()
    {
        try {

            $resultado = DB::table('motivopagoservicio')
                ->select('Codigo', 'Nombre', 'Descripcion')
                ->where('Vigente', 1) // 
                ->get();
            Log::info('Motivos de pago por servicio listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivoPagoServicio',
                'Cantidad' => count($resultado),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al listar motivos de pago por servicio', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivoPagoServicio',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function personalAutorizado($sede)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d');
        try {
            $trabajadores = DB::table('trabajadors as t')
                ->select('t.Codigo', 'p.Nombres', 'p.Apellidos')
                ->join('asignacion_sedes as ass', 'ass.CodigoTrabajador', '=', 't.Codigo')
                ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
                ->where('t.AutorizaDescuento', 1)
                ->where('t.Vigente', 1)
                ->where('p.Vigente', 1)
                ->where('t.tipo', 'A')
                ->where('ass.Vigente', 1)
                ->where('ass.CodigoSede', $sede)
                ->where(function ($query) use ($fecha) {
                    $query->whereNull('ass.FechaFin')
                        ->orWhere('ass.FechaFin', '>=', $fecha);
                })
                ->get();

            Log::info('Personal Autorizado listado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'personalAutorizado',
                'Cantidad' => count($trabajadores),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($trabajadores);
        } catch (\Exception $e) {
            Log::error('Error al listar Personal Autorizado', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'personalAutorizado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function personal($sede)
    {
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d');
        try {
            $trabajadores = DB::table('trabajadors as t')
                ->select('t.Codigo', 'p.Nombres', 'p.Apellidos')
                ->join('asignacion_sedes as ass', 'ass.CodigoTrabajador', '=', 't.Codigo')
                ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
                ->where('t.Vigente', 1)
                ->where('p.Vigente', 1)
                ->where('ass.Vigente', 1)
                ->where('ass.CodigoSede', $sede)
                ->where(function ($query) {
                    $query->whereNull('ass.FechaFin')
                        ->orWhere('ass.FechaFin', '>=', '2025-07-22');
                })
                ->get();

            Log::info('Personal listado correctamente', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'personal',
                'Sede' => $sede,
                'Cantidad' => count($trabajadores),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($trabajadores);
        } catch (\Exception $e) {
            Log::error('Error al listar personal', [
                'Controlador' => 'ContratoProductoeController',
                'Metodo' => 'personal',
                'Sede' => $sede,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarTipoMoneda()
    {
        try {
            $resp = DB::table('tipomoneda')
                ->select('Codigo', 'Nombre', 'Siglas')
                ->where('Vigente', 1)
                ->get();
            Log::info('Tipos de moneda listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTipoMoneda',
                'Cantidad' => count($resp),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($resp);
        } catch (\Exception $e) {
            Log::error('Error al listar tipos de moneda', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarTipoMoneda',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMedicos($sede)
    { //Para Contrato y Ventas
        date_default_timezone_set('America/Lima');
        $fecha = date('Y-m-d');
        try {

            $resultado = DB::table('trabajadors as t')
                ->join('asignacion_sedes as ags', 'ags.CodigoTrabajador', '=', 't.Codigo')
                ->join('personas as p', 'p.Codigo', '=', 't.Codigo')
                ->select('t.Codigo as Codigo', 'p.Apellidos', 'p.Nombres')
                ->where('p.Vigente', 1)
                ->where('t.Vigente', 1)
                ->where('ags.Vigente', 1)
                ->where(function ($query) use ($fecha) {
                    $query->where('ags.FechaFin', '>=', $fecha)
                        ->orWhereNull('ags.FechaFin');
                })
                ->where('ags.CodigoSede', $sede)
                ->where('t.Tipo', 'M')
                ->orderBy('p.Apellidos', 'asc')
                ->get();
            Log::info('Médicos listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMedicos',
                'Sede' => $sede,
                'Cantidad' => count($resultado),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al listar médicos', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMedicos',
                'Sede' => $sede,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarPacientes($sede)
    {
        try {
            $resultado = DB::table('personas as p')
                ->join('sedesrec as s', 's.CodigoDepartamento', '=', 'p.CodigoDepartamento')
                ->select('p.Codigo', 'p.Nombres', 'p.Apellidos')
                ->where('p.Vigente', 1)
                ->where('s.Vigente', 1)
                ->where('s.Codigo', $sede)
                ->get();
            Log::info('Pacientes listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarPacientes',
                'Sede' => $sede,
                'Cantidad' => count($resultado),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al listar pacientes', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarPacientes',
                'Sede' => $sede,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMotivoNotaCredito()
    {
        try {
            $resultado = DB::table('motivonotacredito')
                ->select('Codigo', 'Nombre', 'CodigoSUNAT')
                ->where('Vigente', 1)
                ->get();
            Log::info('Motivos de nota de crédito listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivoNotaCredito',
                'Cantidad' => count($resultado),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al listar motivos de nota de crédito', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivoNotaCredito',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarMotivoAnulacionContrato()
    {
        try {
            $motivos = DB::table('motivoanulacioncontrato')
                ->where('Vigente', 1)
                ->select('Codigo', 'Nombre', 'Descripcion')
                ->get();
            Log::info('Motivos de anulación de contrato listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivoAnulacionContrato',
                'Cantidad' => count($motivos),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($motivos);
        } catch (\Exception $e) {
            Log::error('Error al listar motivos de anulación de contrato', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarMotivoAnulacionContrato',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }

    public function listarDonantes()
    {
        try {
            $personas = DB::table('personas')
                ->select(
                    'Codigo',
                    DB::raw("CONCAT(Apellidos, ' ', Nombres) as Nombres")
                )
                ->where('Vigente', 1)
                ->get();
            Log::info('Donantes listados correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarDonantes',
                'Cantidad' => count($personas),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json($personas);
        } catch (\Exception $e) {
            Log::error('Error al listar donantes', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarDonantes',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }


    public function listarCategoriaProducto()
    {
        try {
            $categoria = DB::table('categoriaproducto')
                ->select(
                    'Codigo',
                    'Nombre'
                )
                ->where('Vigente', 1)
                ->get();

            Log::info('Categorías de productos listadas correctamente', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarCategoriaProducto',
                'Cantidad' => count($categoria),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($categoria);
        } catch (\Exception $e) {
            Log::error('Error al listar categorías de productos', [
                'Controlador' => 'ControladorGeneralController',
                'Metodo' => 'listarCategoriaProducto',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json('Error en la consulta: ' . $e->getMessage());
        }
    }
}
