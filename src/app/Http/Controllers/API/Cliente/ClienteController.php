<?php

namespace App\Http\Controllers\API\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portada\ActualizarRequest;
use App\Http\Requests\Recaudacion\Cliente\RegistrarRequest;
use App\Http\Requests\Recaudacion\Cliente\ActualizarRequest as ClienteActualizarRequest;
use App\Http\Requests\Recaudacion\ClienteEmpresa\RegistrarRequest as ClienteEmpresaRegistrarRequest;
use App\Http\Requests\Recaudacion\ClienteEmpresa\ActualizarRequest as ClienteEmpresaActualizarRequest;
use App\Http\Resources\Recaudacion\Cliente\Cliente;
use App\Http\Resources\Recaudacion\Cliente\ClienteEmpresa;
use App\Models\Personal\Persona;
use App\Models\Recaudacion\ClienteEmpresa as RecaudacionClienteEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


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
    
            return response()->json([
                'msg' => 'Cliente registrado correctamente',
                'codigo' => $persona->Codigo
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al registrar el cliente'], 400);
        }
    }

    public function actualizarCliente(ClienteActualizarRequest $request){
        
        
        try {
             // Update the cliente
            $cliente = Persona::find($request->input('Codigo'));

            if (!$cliente) {
                return response()->json(['msg' => 'Cliente no encontrado'], 404);
            }

            $cliente->update($request->all());
            return response()->json(['msg' => 'Cliente actualizado correctamente'],200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el cliente' ], 400);
        }
    }



    public function registrarClienteEmpresa(ClienteEmpresaRegistrarRequest $request)
    {
        try {
            return (new ClienteEmpresa(RecaudacionClienteEmpresa::create($request->all())))
                ->additional(['msg' => 'Empresa registrada correctamente']);
        } catch (\Exception $e) {
            return response()->json('Error al registrar la empresa', 400);
        }
    }

    public function actualizarClienteEmpresa(ClienteEmpresaActualizarRequest $request)
    {
        try {
            $cliente = RecaudacionClienteEmpresa::find($request->input('Codigo'));
    
            if (!$cliente) {
                return response()->json(['msg' => 'Cliente no encontrado'], 404);
            }
    
            $cliente->update($request->all());
            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el cliente', 'details' => $e->getMessage()], 400);
        }
    }

    public function consultaDatosCliente(Request $request){
        $tipo = $request->input('tipo');
        $id = $request->input('id');

        try{
            if($tipo === 0){ //Cliente
                $cliente = DB::table('clinica_db.personas')
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

                return response()->json($cliente);
            }else{ //Empresa
                $cliente = DB::table('clinica_db.clienteempresa')
                ->select(
                    'RazonSocial',
                    'RUC',
                    'Direccion',
                )
                ->where('Codigo', '=', $id)
                ->where('Vigente', '=', 1)
                ->first();
                return response()->json($cliente);
            }


        }catch(\Exception $e){
            return response()->json('Error al obtener los datos', 400);
        }

    }

    public function consultaCliente(Request $request)
    {
        $tipo = $request->input('tipo');
        $id = $request->input('id');

        if ($tipo === 0) { //Cliente
            $cliente = DB::table('clinica_db.personas')
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
                ->where('Vigente', '=', 1)
                ->first();

            return response()->json($cliente);
        } else { //Empresa
            $cliente = DB::table('clinica_db.clienteempresa')
                ->select(
                    'Codigo',
                    'RazonSocial',
                    'RUC',
                    'Direccion',
                    'CodigoDepartamento',
                    'Vigente'
                )
                ->where('Codigo', '=', $id)
                ->where('Vigente', '=', 1)
                ->first();
            return response()->json($cliente);
        }
    }

    public function listaCliente(Request $request)
    {

        $tipo = $request->input('tipo');
        $nombre = $request->input('nombre');
        $sede = $request->input('sede');

        if ($tipo === 0) { //Cliente
            try {

                $cliente = DB::table('clinica_db.sedesrec as s')
                    ->join('clinica_db.departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
                    ->join('clinica_db.personas as p', 'p.CodigoDepartamento', '=', 'd.Codigo')
                    ->join('clinica_db.tipo_documentos as td', 'td.Codigo', '=', 'p.CodigoTipoDocumento')

                    ->select('p.Codigo as Codigo', 'p.Nombres as Nombres', 'p.Apellidos as Apellidos', 'p.NumeroDocumento as NumeroDocumento', 'td.Siglas as DescTipoDocumento')
                    ->where('p.Nombres', 'like', '%' . $nombre . '%')
                    ->where('s.Codigo', '=', $sede)
                    ->where('p.Vigente', '=', 1)
                    ->get();

                return response()->json($cliente);
            } catch (\Exception $e) {
                return response()->json('Error al obtener los datos', 400);
            }
        } else { //Empresa

            $cliente = DB::table('clinica_db.sedesrec as s')
                ->join('clinica_db.departamentos as d', 'd.Codigo', '=', 's.CodigoDepartamento')
                ->join('clinica_db.clienteempresa as e', 'e.CodigoDepartamento', '=', 'd.Codigo')
                ->select('e.Codigo as Codigo', 'e.RazonSocial as RazonSocial', 'e.RUC as RUC')
                ->where('e.RazonSocial', 'like', '%' . $nombre . '%')
                ->where('s.Codigo', '=', $sede)
                ->where('e.Vigente', '=', 1)
                ->get();
            return response()->json($cliente);
        }
    }
}
