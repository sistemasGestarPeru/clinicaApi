<?php

namespace App\Http\Controllers\API\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portada\ActualizarRequest;
use App\Http\Requests\Recaudacion\Cliente\RegistrarRequest;
use App\Http\Requests\Recaudacion\Cliente\ActualizarRequest as ClienteActualizarRequest;
use App\Http\Requests\Recaudacion\ClienteEmpresa\RegistrarRequest as ClienteEmpresaRegistrarRequest;
use App\Http\Requests\Recaudacion\ClienteEmpresa\ActualizarRequest as ClienteEmpresaActualizarRequest;
use App\Models\Personal\Persona;
use App\Models\Recaudacion\ClienteEmpresa as RecaudacionClienteEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ClienteController extends Controller
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
    public function update(Request $request, Persona $cliente) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function registrarCliente(RegistrarRequest $request)
    {
        try {
            // Crear la persona y capturar su instancia
            $persona = Persona::create($request->all());


            // Log de éxito
            Log::info('Cliente registrado correctamente', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'registrarCliente',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);


            return response()->json([
                'msg' => 'Cliente registrado correctamente',
                'codigo' => $persona->Codigo
            ]);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error al registrar el cliente.', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'registrarCliente',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json(['error' => 'Error al registrar el cliente'], 400);
        }
    }

    public function actualizarCliente(ClienteActualizarRequest $request)
    {

        try {
            // Update the cliente
            $cliente = Persona::find($request->input('Codigo'));

            if (!$cliente) {

                // Log del error específico
                Log::warning('Cliente no encontrado', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'actualizarCliente',
                    'Codigo' => $request->input('Codigo'),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json(['msg' => 'Cliente no encontrado'], 404);
            }

            // Log de éxito
            Log::info('Cliente actualizado correctamente', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'actualizarCliente',
                'Codigo' => $request->input('Codigo'),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            $cliente->update($request->all());
            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al actualizar el cliente.', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'actualizarCliente',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al actualizar el cliente'], 400);
        }
    }



    public function registrarClienteEmpresa(ClienteEmpresaRegistrarRequest $request)
    {
        try {
            // Crear la persona y capturar su instancia
            $clienteEmpresa = RecaudacionClienteEmpresa::create($request->all());

            // Log de éxito
            Log::info('Cliente Empresa registrado correctamente', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'registrarClienteEmpresa',
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json([
                'msg' => 'Cliente registrado correctamente',
                'codigo' => $clienteEmpresa->Codigo
            ]);
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error al registrar el cliente empresa.', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'registrarClienteEmpresa',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al registrar el cliente'], 400);
        }
    }

    public function actualizarClienteEmpresa(ClienteEmpresaActualizarRequest $request)
    {
        try {
            $cliente = RecaudacionClienteEmpresa::find($request->input('Codigo'));

            if (!$cliente) {
                // Log del error específico
                Log::warning('Cliente Empresa no encontrado', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'actualizarClienteEmpresa',
                    'Codigo' => $request->input('Codigo'),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);
                return response()->json(['msg' => 'Cliente no encontrado'], 404);
            }

            $cliente->update($request->all());
            // Log de éxito
            Log::info('Cliente Empresa actualizado correctamente', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'actualizarClienteEmpresa',
                'Codigo' => $request->input('Codigo'),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al actualizar el cliente empresa.', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'actualizarClienteEmpresa',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);
            return response()->json(['error' => 'Error al actualizar el cliente', 'details' => $e->getMessage()], 400);
        }
    }

    public function consultaDatosCliente(Request $request)
    {
        $tipo = $request->input('tipo');
        $id = $request->input('id');

        try {
            if ($tipo === 0) { //Cliente
                $cliente = DB::table('personas')
                    ->select(
                        'Nombres',
                        'Apellidos',
                        'Direccion',
                        'Celular',
                        'Correo',
                        'NumeroDocumento',
                        'CodigoTipoDocumento',
                    )
                    ->where('Codigo', '=', $id)
                    ->where('Vigente', '=', 1)
                    ->first();

                if (!$cliente) {
                    // Log del error específico
                    Log::warning('Cliente no encontrado', [
                        'Controlador' => 'ClienteController',
                        'Metodo' => 'consultaDatosCliente',
                        'Codigo' => $id,
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['msg' => 'Cliente no encontrado'], 500);
                }

                // Log de éxito
                Log::info('Datos del cliente obtenidos correctamente', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'consultaDatosCliente',
                    'Codigo' => $id,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json($cliente);
            } else { //Empresa
                $cliente = DB::table('clienteempresa')
                    ->select(
                        'RazonSocial',
                        'RUC',
                        'Direccion',
                    )
                    ->where('Codigo', '=', $id)
                    ->where('Vigente', '=', 1)
                    ->first();

                if (!$cliente) {
                    // Log del error específico
                    Log::warning('Cliente Empresa no encontrado', [
                        'Controlador' => 'ClienteController',
                        'Metodo' => 'consultaDatosCliente',
                        'Codigo' => $id,
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['msg' => 'Cliente Empresa no encontrado'], 500);
                }

                // Log de éxito
                Log::info('Datos del cliente empresa obtenidos correctamente', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'consultaDatosCliente',
                    'Codigo' => $id,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json($cliente);
            }
        } catch (\Exception $e) {

            // Log del error general
            Log::error('Error al obtener los datos del cliente.', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'consultaDatosCliente',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Error al obtener los datos', 500);
        }
    }

    public function consultaCliente(Request $request)
    {
        $tipo = $request->input('tipo');
        $id = $request->input('id');

        try {
            if ($tipo === 0) { //Cliente
                $cliente = DB::table('personas')
                    ->select(
                        'Codigo',
                        'Nombres',
                        'Apellidos',
                        'Direccion',
                        'Celular',
                        'Correo',
                        'NumeroDocumento',
                        'CodigoTipoDocumento',
                        'CodigoNacionalidad',
                        'CodigoDepartamento',
                        'Vigente'
                    )
                    ->where('Codigo', '=', $id)
                    ->first();

                if (!$cliente) {
                    // Log del error específico
                    Log::warning('Cliente no encontrado', [
                        'Controlador' => 'ClienteController',
                        'Metodo' => 'consultaCliente',
                        'Codigo' => $id,
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['msg' => 'Cliente no encontrado'], 404);
                }

                return response()->json($cliente);
            } else { //Empresa
                $cliente = DB::table('clienteempresa')
                    ->select(
                        'Codigo',
                        'RazonSocial',
                        'RUC',
                        'Direccion',
                        'CodigoDepartamento',
                        'Vigente'
                    )
                    ->where('Codigo', '=', $id)
                    ->first();

                if (!$cliente) {
                    // Log del error específico
                    Log::warning('Cliente Empresa no encontrado', [
                        'Controlador' => 'ClienteController',
                        'Metodo' => 'consultaCliente',
                        'Codigo' => $id,
                        'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                    ]);
                    return response()->json(['msg' => 'Cliente Empresa no encontrado'], 404);
                }

                // Log de éxito
                Log::info('Cliente Empresa consultado correctamente', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'consultaCliente',
                    'Codigo' => $id,
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json($cliente);
            }
        } catch (\Exception $e) {
            // Log del error general
            Log::error('Error al consultar el cliente.', [
                'Controlador' => 'ClienteController',
                'Metodo' => 'consultaCliente',
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
                'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
            ]);

            return response()->json('Error al obtener los datos', 500);
        }
    }

    public function listaCliente(Request $request)
    {

        $tipo = $request->input('tipo');
        $nombre = $request->input('nombre');
        $documento = $request->input('documento');

        if ($tipo === 0) { //Cliente
            try {

                $cliente = DB::table('personas as p')
                    ->join('tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')
                    ->select(
                        'p.Codigo',
                        'p.Nombres',
                        'p.Apellidos',
                        'p.NumeroDocumento',
                        'td.Siglas as DescTipoDocumento',
                        'p.Vigente'
                    )
                    ->when(!empty($nombre), function ($query) use ($nombre) {
                        return $query->where(function ($q) use ($nombre) {
                            $q->where('p.Nombres', 'LIKE', "$nombre%")
                                ->orWhere('p.Apellidos', 'LIKE', "$nombre%");
                        });
                    })
                    ->when(!empty($documento), function ($query) use ($documento) {
                        return $query->where('p.NumeroDocumento', 'LIKE', "$documento%");
                    })
                    ->get();

                // Log de éxito
                Log::info('Lista de clientes obtenida correctamente', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'listaCliente',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json($cliente);
            } catch (\Exception $e) {

                // Log del error general
                Log::error('Error al obtener la lista de clientes.', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'listaCliente',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json('Error al obtener los datos', 400);
            }
        } else { //Empresa

            try {
                $cliente = DB::table('clienteempresa as e')
                    ->select(
                        'e.Codigo',
                        'e.RazonSocial',
                        'e.RUC',
                        'e.Vigente'
                    )
                    ->when(!empty($nombre), function ($query) use ($nombre) {
                        return $query->where('e.RazonSocial', 'LIKE', "$nombre%");
                    })
                    ->when(!empty($documento), function ($query) use ($documento) {
                        return $query->where('e.RUC', 'LIKE', "$documento%");
                    })

                    ->get();

                // Log de éxito
                Log::info('Lista de clientes empresa obtenida correctamente', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'listaCliente',
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json($cliente);
            } catch (\Exception $e) {

                // Log del error general
                Log::error('Error al obtener la lista de clientes empresa.', [
                    'Controlador' => 'ClienteController',
                    'Metodo' => 'listaCliente',
                    'mensaje' => $e->getMessage(),
                    'linea' => $e->getLine(),
                    'archivo' => $e->getFile(),
                    'usuario_actual' => auth()->check() ? auth()->user()->id : 'no autenticado'
                ]);

                return response()->json('Error al obtener los datos', 400);
            }
        }
    }
}
