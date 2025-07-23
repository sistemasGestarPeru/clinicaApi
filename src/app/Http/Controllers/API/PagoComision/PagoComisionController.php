<?php

namespace App\Http\Controllers\API\PagoComision;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Egreso\GuardarEgresoRequest;
use App\Models\Recaudacion\Comision;
use App\Models\Recaudacion\DetalleComision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Recaudacion\Egreso;
use App\Models\Recaudacion\ValidacionCaja\MontoCaja;
use App\Models\Recaudacion\PagoComision;
use App\Models\Recaudacion\ValidacionCaja\ValidarFecha;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PagoComisionController extends Controller
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

    public function listarMedicosPendientesP($sede)
    {

        try {

            $medicos = DB::table('comision as c')

                ->join('personas as p', 'p.Codigo', '=', 'c.CodigoMedico')
                ->whereNull('c.CodigoPagoComision')
                ->distinct()
                ->select('c.CodigoMedico as Codigo', DB::raw("CONCAT(p.Nombres, ' ', p.Apellidos) as Medico"))
                ->get();

            //log info
            Log::info('Listar Médicos Pendientes de Pago', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarMedicosPendientesP',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json($medicos, 200);
        } catch (\Exception $e) {
            Log::error('Error al listar los médicos pendientes', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarMedicosPendientesP',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine()
            ]);
            return response()->json([
                'message' => 'Error al listar los medicos pendientes',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarComisionPendiente(Request $request)
    {
        $medico = $request->input('medico');
        $comision = $request->input('comisionPendiente');
        $dataEgreso = $request->input('egreso');

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

            $total = MontoCaja::obtenerTotalCaja($dataEgreso['CodigoCaja']);

            if ($dataEgreso['Monto'] > $total) {
                Log::warning('Error al registrar comisión', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'registrarComisionPendiente',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => __('mensajes.error_sin_efectivo', ['total' => $total]),
                    'Disponible' => $total
                ]);
                return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
            }
        } else if ($dataEgreso['CodigoSUNAT'] == '003') {
            $dataEgreso['Lote'] = null;
            $dataEgreso['Referencia'] = null;
        } else if ($dataEgreso['CodigoSUNAT'] == '005' || $dataEgreso['CodigoSUNAT'] == '006') {
            $dataEgreso['CodigoCuentaBancaria'] = null;
            $dataEgreso['CodigoBilleteraDigital'] = null;
        }

        $fechaCajaObj = ValidarFecha::obtenerFechaCaja($dataEgreso['CodigoCaja']);
        $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
        $fechaEgresoVal = Carbon::parse($dataEgreso['Fecha'])->toDateString(); // Convertir el string a Carbon


        if ($fechaCajaVal < $fechaEgresoVal) {
            Log::warning('Error al registrar comisión', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'registrarComisionPendiente',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => __('mensajes.error_fecha_pago')
            ]);
            return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
        }
        //Validar Egreso
        $egresoValidator = Validator::make($dataEgreso, (new GuardarEgresoRequest())->rules());
        $egresoValidator->validate();
        DB::beginTransaction();
        try {

            $egresoCreado = Egreso::create($dataEgreso)->Codigo;

            $pagoComision = ['Codigo' => $egresoCreado, 'CodigoMedico' => $medico];
            PagoComision::create($pagoComision);

            foreach ($comision as $item) {
                DB::table('comision')
                    ->where('Codigo', $item['Codigo'])
                    ->update([
                        'CodigoPagoComision' => $egresoCreado,
                        'Serie' => $item['Serie'],
                        'Numero' => $item['Numero'],
                    ]);
            }

            DB::commit();
            Log::info('Comisión registrada correctamente', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'registrarComisionPendiente',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json([
                'message' => 'Comisión registrada correctamente'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar comisión', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'registrarComisionPendiente',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json([
                'error' => 'Error al registrar la comisión',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarPagoComision(Request $request)
    {
        //fecha actual en yyyy-mm-dd
        $fechaActual = Carbon::now()->toDateString();

        $egreso = $request->input('egreso');
        $pagoComision = $request->input('pagoComision');
        $comision = $request->input('comision');
        $detalleComision = $request->input('detalleComision');

        $comision['FechaCreacion'] = $fechaActual; // Asignar fecha de creación

        if ($egreso) {
            //Validar Egreso
            $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
            $egresoValidator->validate();

            if (isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0) {
                $egreso['CodigoCuentaOrigen'] = null;
            }

            if (isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0) {
                $egreso['CodigoBilleteraDigital'] = null;
            }

            $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
            $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
            $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

            if ($fechaCajaVal < $fechaVentaVal) {
                Log::warning('Error al registrar pago de comisión', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'registrarPagoComision',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => __('mensajes.error_fecha_pago')
                ]);
                return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
            }


            if ($egreso['CodigoSUNAT'] == '008') {
                $egreso['CodigoCuentaOrigen'] = null;
                $egreso['CodigoBilleteraDigital'] = null;
                $egreso['Lote'] = null;
                $egreso['Referencia'] = null;
                $egreso['NumeroOperacion'] = null;

                $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

                if ($egreso['Monto'] > $total) {
                    Log::warning('Error al registrar pago de comisión', [
                        'Controlador' => 'PagoComisionController',
                        'Metodo' => 'registrarPagoComision',
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                        'mensaje' => __('mensajes.error_sin_efectivo', ['total' => $total]),
                        'Disponible' => $total
                    ]);
                    return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
                }
            } else if ($egreso['CodigoSUNAT'] == '003') {
                $egreso['Lote'] = null;
                $egreso['Referencia'] = null;
            } else if ($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006') {
                $egreso['CodigoCuentaBancaria'] = null;
                $egreso['CodigoBilleteraDigital'] = null;
            }
        }


        DB::beginTransaction();
        try {

            $comision['CodigoDocumentoVenta'] = $comision['CodigoDocumentoVenta'] == 0 ? null : $comision['CodigoDocumentoVenta'];
            $comision['CodigoContrato'] = $comision['CodigoContrato'] == 0 ? null : $comision['CodigoContrato'];

            if ($egreso && $pagoComision) {
                $egreso = Egreso::create($egreso);
                $pagoComision['Codigo'] = $egreso->Codigo;
                $codigoPagoComision = PagoComision::create($pagoComision);
                $comision['CodigoPagoComision'] = $egreso->Codigo;
            }

            $codigoComision = Comision::create($comision)->Codigo;

            foreach ($detalleComision as $detalle) {
                $detalle['CodigoComision'] = $codigoComision;
                if (isset($detalle['CodigoDetalleVenta'])) {
                    $detalle['CodigoDetalleVenta'] = $detalle['CodigoDetalleVenta'] == 0 ? null : $detalle['CodigoDetalleVenta'];
                }
                if (isset($detalle['CodigoDetalleContrato'])) {
                    $detalle['CodigoDetalleContrato'] = $detalle['CodigoDetalleContrato'] == 0 ? null : $detalle['CodigoDetalleContrato'];
                }
                DetalleComision::create($detalle);
            }

            DB::commit();

            Log::info('Pago de comisión registrado correctamente', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'registrarPagoComision',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'message' => 'Pago de comisión registrado correctamente'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar el pago de comisión', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'registrarPagoComision',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json([
                'error' => 'Error al registrar el Pago de Comisión',
                'bd' => $e->getMessage(),
            ], 500);
        }
    }


    public function listarComisionesPagar($sede, $medico)
    {
        //falta la sede
        try {
            $comisiones = DB::table('comision as c')
                ->leftJoin('pagocomision as pc', 'c.CodigoPagoComision', '=', 'pc.Codigo')
                ->leftJoin('documentoventa as dv', 'c.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('contratoproducto as cp', 'c.CodigoContrato', '=', 'cp.Codigo')
                ->leftJoin('personas as p', 'c.CodigoMedico', '=', 'p.Codigo')
                ->select(
                    'c.Codigo',
                    DB::raw('p.Codigo AS CodigoMedico'),
                    DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as Medico"),
                    'c.TipoDocumento as TipoDocumento',
                    'c.Monto',
                    'c.Serie',
                    'c.Numero',
                    'c.Vigente'
                )
                ->whereNull('c.CodigoPagoComision')
                ->where('c.CodigoMedico', $medico) // <- reemplaza por tu variable si es necesario
                ->where('c.Vigente', 1)
                ->where(function ($query) use ($sede) {
                    $query->where(function ($q) use ($sede) {
                        $q->where('dv.CodigoSede', $sede)
                            ->whereNotNull('dv.Codigo');
                    })->orWhere(function ($q) use ($sede) {
                        $q->where('cp.CodigoSede', $sede)
                            ->whereNotNull('cp.Codigo');
                    });
                })
                ->get();

            //log info
            Log::info('Listar Comisiones por Pagar', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarComisionesPagar',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'Cantidad' => count($comisiones)
            ]);

            return response()->json($comisiones, 200);
        } catch (\Exception $e) {

            Log::error('Error al listar las comisiones por pagar', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarComisionesPagar',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'message' => 'Error al listar las comisiones por pagar',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function listarPagosComisiones(Request $request)
    {
        $data = $request->input('data');
        $sede = $data['CodigoSede'];
        $trabajador = $data['CodigoTrabajador'];
        $fecha = $data['fecha'];

        try {

            $resultados = DB::table('comision as c')
                ->leftJoin('pagocomision as pc', 'c.CodigoPagoComision', '=', 'pc.Codigo')
                ->leftJoin('egreso as e', 'e.Codigo', '=', 'pc.Codigo')
                ->leftJoin('documentoventa as dv', 'c.CodigoDocumentoVenta', '=', 'dv.Codigo')
                ->leftJoin('contratoproducto as cp', 'c.CodigoContrato', '=', 'cp.Codigo')
                ->leftJoin('personas as p', 'c.CodigoMedico', '=', 'p.Codigo')
                ->select(
                    'c.Codigo',
                    'e.Codigo as Egreso',
                    DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as Medico"),
                    DB::raw("CASE 
                            WHEN c.TipoDocumento = 'R' THEN 'Recibo por Honorario' 
                            ELSE 'Nota de Pago' 
                        END AS TipoDocumento"),
                    'c.Monto',
                    DB::raw("CONCAT(c.Serie, ' - ', c.Numero) as Documento"),
                    DB::raw("DATE(e.Fecha) as FechaPago"),
                    DB::raw("DATE(c.FechaCreacion) as FechaRegistro"),
                    DB::raw("CASE 
                        WHEN e.Codigo IS NULL THEN c.Vigente
                                            ELSE e.Vigente
                        END AS Vigente")
                )
                ->where(function ($query) use ($sede) {
                    $query->where('cp.CodigoSede', $sede)
                        ->orWhere('dv.CodigoSede', $sede);
                })
                ->where('c.CodigoTrabajador', $trabajador)
                ->when(!empty($data['fecha']), function ($query) use ($data) {
                    $query->where(function ($sub) use ($data) {
                        $sub->whereDate('c.FechaCreacion', '=', $data['fecha'])
                            ->orWhereNull('c.FechaCreacion');
                    });
                })
                ->get();

            //log info
            Log::info('Listar Pagos de Comisiones', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarPagosComisiones',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'Cantidad' => count($resultados)
            ]);

            return response()->json($resultados, 200);
        } catch (\Exception $e) {

            Log::error('Error al listar los pagos de comisiones', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarPagosComisiones',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'message' => 'Error al listar los pagos de comisiones',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarPagoComision($codigo)
    {
        try {
            $comision = Comision::find($codigo);
            $egreso = Egreso::find($comision->CodigoPagoComision);

            $detalleComision = DB::table('comision as c')
                ->join('detallecomision as dc', 'c.Codigo', '=', 'dc.CodigoComision')
                ->leftJoin('detallecontrato as dcont', 'dc.CodigoDetalleContrato', '=', 'dcont.Codigo')
                ->leftJoin('detalledocumentoventa as ddv', 'dc.CodigoDetalleVenta', '=', 'ddv.Codigo')
                ->leftJoin('producto as p1', 'dcont.CodigoProducto', '=', 'p1.Codigo')
                ->leftJoin('producto as p2', 'ddv.CodigoProducto', '=', 'p2.Codigo')
                ->select([
                    'dc.Codigo as DetComision',
                    'dc.Monto',
                    DB::raw("CASE 
                                WHEN dcont.Codigo IS NOT NULL THEN p1.Nombre 
                                ELSE p2.Nombre 
                            END AS Descripcion")
                ])
                ->where('dc.CodigoComision', $codigo)
                ->get();


            $paciente = DB::table('comision as c')
                ->leftJoin('documentoventa as dv', 'dv.Codigo', '=', 'c.CodigoDocumentoVenta')
                ->leftJoin('contratoproducto as cp', 'cp.Codigo', '=', 'c.CodigoContrato')
                ->leftJoin('personas as pDV', 'pDV.Codigo', '=', 'dv.CodigoPaciente')
                ->leftJoin('personas as pCON', 'pCON.Codigo', '=', 'cp.CodigoPaciente')
                ->selectRaw("
                    CASE 
                        WHEN dv.CodigoPaciente IS NOT NULL THEN CONCAT(pDV.Apellidos, ' ', pDV.Nombres)
                        WHEN cp.CodigoPaciente IS NOT NULL THEN CONCAT(pCON.Apellidos, ' ', pCON.Nombres)
                        ELSE 'No encontrado'
                    END AS Paciente,
                    CASE 
                        WHEN dv.CodigoPaciente IS NOT NULL THEN CONCAT(dv.Serie, ' - ', LPAD(dv.Numero, 5, '0'))
                        WHEN cp.CodigoPaciente IS NOT NULL THEN LPAD(cp.NumContrato, 5, '0')
                        ELSE 'No encontrado'
                    END AS Documento,
                    CASE 
                        WHEN dv.CodigoPaciente IS NOT NULL THEN DATE(dv.Fecha)
                        WHEN cp.CodigoPaciente IS NOT NULL THEN DATE(cp.Fecha)
                        ELSE 'No encontrado'
                    END AS Fecha
                ")
                ->where('c.Codigo', $codigo)
                ->first();




            if ($comision) {

                Log::info('Consulta de Pago de Comisión', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'consultarPagoComision',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo' => $codigo
                ]);

                return response()->json([
                    'comision' => $comision,
                    'detalleComision' => $detalleComision,
                    'egreso' => $egreso,
                    'paciente' => $paciente
                ], 200);
            } else {
                Log::warning('Pago de Comisión no encontrado', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'consultarPagoComision',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo' => $codigo
                ]);
                return response()->json([
                    'error' => 'Pago de comisión no encontrado'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error al consultar el pago de comisión', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'consultarPagoComision',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $codigo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json([
                'error' => 'Error al consultar el pago de comisión',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarPagoComision(Request $request)
    {
        //revisar todo
        $egreso = $request->input('egreso');
        $comision = $request->input('comision');

        DB::beginTransaction();

        try {

            $comisionData = Comision::find($comision['Codigo']);
            $estadoCaja = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);

            if (!$comisionData) {
                Log::warning('Comisión no encontrada', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'actualizarPagoComision',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'codigo' => $comision['Codigo']
                ]);
                return response()->json([
                    'error' => 'No se ha encontrado la Comisión.'
                ], 404);
            }

            if ($estadoCaja->Estado == 'C') {
                Log::warning('Error al actualizar el pago de comisión', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'actualizarPagoComision',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => __('mensajes.error_act_egreso_caja', ['tipo' => 'pago varios'])
                ]);
                return response()->json([
                    'error' => __('mensajes.error_act_egreso_caja', ['tipo' => 'pago varios']),
                ], 400);
            }

            if ($comisionData['Vigente'] == 0) {
                Log::warning('Error al actualizar el pago de comisión', [
                    'Controlador' => 'PagoComisionController',
                    'Metodo' => 'actualizarPagoComision',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                    'mensaje' => __('mensajes.error_act_egreso', ['tipo' => 'servicio'])
                ]);
                return response()->json([
                    'error' => __('mensajes.error_act_egreso', ['tipo' => 'servicio']),
                ], 400);
            }

            if (isset($egreso['Codigo']) && $egreso['Codigo'] != 0) {

                if ($comision['Vigente'] == 0) {

                    $egresoData = Egreso::find($egreso['Codigo']);
                    $egresoData->update(['Vigente' => 0]);

                    $comisionData->update(['Vigente' => 0]);
                }
            } else if (isset($egreso) && !isset($egreso['Codigo']) && $comision['Vigente'] == 1) {

                //Validar Egreso
                $egresoValidator = Validator::make($egreso, (new GuardarEgresoRequest())->rules());
                $egresoValidator->validate();

                if (isset($egreso['CodigoCuentaOrigen']) && $egreso['CodigoCuentaOrigen'] == 0) {
                    $egreso['CodigoCuentaOrigen'] = null;
                }

                if (isset($egreso['CodigoBilleteraDigital']) && $egreso['CodigoBilleteraDigital'] == 0) {
                    $egreso['CodigoBilleteraDigital'] = null;
                }

                $fechaCajaObj = ValidarFecha::obtenerFechaCaja($egreso['CodigoCaja']);
                $fechaCajaVal = Carbon::parse($fechaCajaObj->FechaInicio)->toDateString(); // Suponiendo que el campo es "FechaCaja"
                $fechaVentaVal = Carbon::parse($egreso['Fecha'])->toDateString(); // Convertir el string a Carbon

                if ($fechaCajaVal < $fechaVentaVal) {
                    Log::warning('Error al actualizar el pago de comisión', [
                        'Controlador' => 'PagoComisionController',
                        'Metodo' => 'actualizarPagoComision',
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                        'mensaje' => __('mensajes.error_fecha_pago')
                    ]);
                    return response()->json(['error' => __('mensajes.error_fecha_pago')], 400);
                }

                if ($egreso['CodigoSUNAT'] == '008') {
                    $egreso['CodigoCuentaOrigen'] = null;
                    $egreso['CodigoBilleteraDigital'] = null;
                    $egreso['Lote'] = null;
                    $egreso['Referencia'] = null;
                    $egreso['NumeroOperacion'] = null;

                    $total = MontoCaja::obtenerTotalCaja($egreso['CodigoCaja']);

                    if ($egreso['Monto'] > $total) {
                        Log::warning('Error al actualizar el pago de comisión', [
                            'Controlador' => 'PagoComisionController',
                            'Metodo' => 'actualizarPagoComision',
                            'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                            'mensaje' => __('mensajes.error_sin_efectivo', ['total' => $total]),
                            'Disponible' => $total
                        ]);
                        return response()->json(['error' => __('mensajes.error_sin_efectivo', ['total' => $total]), 'Disponible' => $total], 500);
                    }
                } else if ($egreso['CodigoSUNAT'] == '003') {
                    $egreso['Lote'] = null;
                    $egreso['Referencia'] = null;
                } else if ($egreso['CodigoSUNAT'] == '005' || $egreso['CodigoSUNAT'] == '006') {
                    $egreso['CodigoCuentaBancaria'] = null;
                    $egreso['CodigoBilleteraDigital'] = null;
                }

                $egreso = Egreso::create($egreso);
                $pagoComision['CodigoMedico'] = $comisionData['CodigoMedico'];
                $pagoComision['Codigo'] = $egreso->Codigo;
                PagoComision::create($pagoComision);
                $comision['CodigoPagoComision'] = $egreso->Codigo;

                $comisionData->update([
                    'CodigoPagoComision' => $comision['CodigoPagoComision'],
                    'TipoDocumento' => $comision['TipoDocumento'],
                    'Serie' => $comision['Serie'],
                    'Numero' => $comision['Numero'],
                    'Comentario' => $comision['Comentario'],
                ]);
            } else if (!isset($egreso['Codigo']) && $comision['Vigente'] == 0) {
                $comisionData->update(['Vigente' => 0]);
            }

            DB::commit();
            Log::info('Pago de Comisión actualizado correctamente', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'actualizarPagoComision',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $comision['Codigo']
            ]);
            return response()->json([
                'message' => 'Pago Comisión actualizado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar el pago de comisión', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'actualizarPagoComision',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $comision['Codigo'],
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json([
                'error' => 'Error al actualizar el pago de comisión',
                'bd' => $e->getMessage()
            ], 500);
        }
    }

    public function listarDocumentos(Request $request)
    {

        $medico = $request->input('medico');
        $sede = $request->input('sede');
        $termino = $request->input('termino');
        $tipoComision = $request->input('tipoComision');

        try {

            if ($tipoComision == 'C') {

                $query = DB::table('contratoproducto as cp')
                    ->distinct()
                    ->join('personas as p', 'p.Codigo', '=', 'cp.CodigoPaciente')
                    ->leftJoin('comision as c', 'cp.Codigo', '=', 'c.CodigoContrato')
                    ->select([
                        'cp.Codigo as Codigo',
                        DB::raw("LPAD(cp.NumContrato, 5, '0') AS Documento"),
                        DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as Paciente"),
                        DB::raw("DATE(cp.Fecha) as Fecha")
                    ])
                    ->where('cp.CodigoMedico', $medico)
                    ->where('cp.CodigoSede', $sede)
                    ->where('cp.Vigente', 1)
                    ->where(function ($q) {
                        $q->whereNull('c.Codigo')
                            ->orWhere('c.Vigente', 0);
                    })
                    ->when($termino, function ($query) use ($termino) {
                        return $query->where(function ($q) use ($termino) {
                            $q->where('p.Nombres', 'LIKE', "{$termino}%")
                                ->orWhere('p.Apellidos', 'LIKE', "{$termino}%");
                        });
                    })
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('detallecontrato as dc')
                            ->join('producto as p', 'dc.CodigoProducto', '=', 'p.Codigo')
                            ->whereRaw('dc.CodigoContrato = cp.Codigo')
                            ->where('p.Tipo', 'S');
                    });
            } else {
                $query = DB::table('documentoventa as dv')
                    ->distinct()
                    ->join('personas as p', 'p.Codigo', '=', 'dv.CodigoPaciente')
                    ->leftJoin('comision as c', 'dv.Codigo', '=', 'c.CodigoDocumentoVenta')
                    ->select([
                        'dv.Codigo as Codigo',
                        DB::raw("CONCAT(dv.Serie,' - ',LPAD(dv.Numero, 5, '0')) as Documento"),
                        DB::raw("CONCAT(p.Apellidos, ' ', p.Nombres) as Paciente"),
                        DB::raw("DATE(dv.Fecha) as Fecha")
                    ])
                    ->where('dv.CodigoMedico', $medico)
                    ->where('dv.Vigente', 1)
                    ->where('dv.CodigoSede', $sede)
                    ->whereNull('dv.CodigoMotivoNotaCredito')
                    ->whereNull('dv.CodigoContratoProducto')
                    ->where(function ($q) {
                        $q->whereNull('c.Codigo')
                            ->orWhere('c.Vigente', 0);
                    })
                    ->where(function ($query) use ($termino) {
                        $query->where('p.Nombres', 'LIKE', "{$termino}%")
                            ->orWhere('p.Apellidos', 'LIKE', "{$termino}%");
                    })
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('detalledocumentoventa as ddv')
                            ->join('producto as p', 'ddv.CodigoProducto', '=', 'p.Codigo')
                            ->whereRaw('ddv.CodigoVenta = dv.Codigo')
                            ->where('p.Tipo', 'S');
                    });
            }
            //log info
            Log::info('Listar Documentos para Comisiones', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarDocumentos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'sede' => $sede,
                'Cantidad' => $query->count()
            ]);
            return response()->json($query->get(), 200);
        } catch (\Exception $e) {
            Log::error('Error al buscar los documentos', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'listarDocumentos',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json([
                'message' => 'Error al buscar los documentos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function consultarDetalleDocumento($codigo, $tipo)
    {
        try {

            if ($tipo == 'C') {

                $detalles = DB::table('contratoproducto as cp')
                    ->join('detallecontrato as dc', 'cp.Codigo', '=', 'dc.CodigoContrato')
                    ->join('producto as p', 'dc.CodigoProducto', '=', 'p.Codigo')
                    ->where('cp.Codigo', $codigo)
                    ->where('p.Tipo', 'S')
                    ->select('dc.Codigo as CodigoDetalleContrato', 'dc.Descripcion');
            } else {
                $detalles = DB::table('documentoVenta as dv')
                    ->join('detalledocumentoventa as ddv', 'dv.Codigo', '=', 'ddv.CodigoVenta')
                    ->join('producto as p', 'ddv.CodigoProducto', '=', 'p.Codigo')
                    ->where('dv.Codigo', $codigo)
                    ->where('p.Tipo', 'S')
                    ->select('ddv.Codigo as CodigoDetalleVenta', 'ddv.Descripcion');
            }
            //log info
            Log::info('Consultar Detalle de Documento', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'consultarDetalleDocumento',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $codigo,
                'tipo' => $tipo,
                'Cantidad' => $detalles->count()
            ]);
            // Retornar en formato JSON (si es necesario)
            return response()->json($detalles->get(), 200);
        } catch (\Exception $e) {
            Log::error('Error al consultar el detalle del documento', [
                'Controlador' => 'PagoComisionController',
                'Metodo' => 'consultarDetalleDocumento',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado',
                'codigo' => $codigo,
                'tipo' => $tipo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);
            return response()->json([
                'error' => 'Error al consultar el documento',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
