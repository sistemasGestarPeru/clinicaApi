<?php

namespace App\Http\Controllers\API\Cliente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recaudacion\Cliente\RegistrarRequest;
use App\Http\Requests\Recaudacion\ClienteEmpresa\RegistrarRequest as ClienteEmpresaRegistrarRequest;
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
            new Cliente(Persona::create($request->all()));
            return response()->json(['msg' => 'Cliente registrado correctamente']);
        } catch (\Exception $e) {
            return response()->json('Error al registrar el cliente', 400);
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

    public function actualizarCliente(Request $request)
    {
        $clienteData = $request->input('cliente');

        // Find the cliente by its Codigo
        $cliente = Persona::find($clienteData['Codigo']);

        if (!$cliente) {
            return response()->json(['msg' => 'Cliente no encontrado'], 404);
        }

        // Define validation rules
        $rules = [
            'NumeroDocumento' => [
                'required',
                'string',
                Rule::unique('personas')
                    ->where('CodigoTipoDocumento', $clienteData['CodigoTipoDocumento'])
                    ->ignore($cliente->Codigo, 'Codigo') // Ignore current record
            ],
            'CodigoTipoDocumento' => 'required|integer',

        ];

        // Validate the request
        $validator = Validator::make($clienteData, $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Update the cliente
            $cliente->update($clienteData);

            return response()->json(['msg' => 'Cliente actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json('Error al actualizar el cliente', 400);
        }
    }

    // public function actualizarClienteEmpresa(Request $request)
    // {
    //     $cliente = $request->input('cliente');

    //     try {
    //         $cliente = RecaudacionClienteEmpresa::find($cliente['Codigo']);

    //         $cliente->update($request->input('cliente'));

    //         return response()->json(['msg' => 'Cliente actualizado correctamente', 'data' => $cliente]);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         return response()->json('Error en la consulta: ' . $e->getMessage());
    //     }
    // }

    public function actualizarClienteEmpresa(Request $request)
    {
        $clienteData = $request->input('cliente');

        // Find the cliente by its Codigo
        $cliente = RecaudacionClienteEmpresa::find($clienteData['Codigo']);

        if (!$cliente) {
            return response()->json(['msg' => 'Cliente no encontrado'], 404);
        }

        $rules = [
            'RUC' => [
                'required',
                'string',
                'min:8',
                Rule::unique('clienteempresa')
                    ->where(function ($query) {
                        return $query->where('vigente', 1);
                    }),
            ],
        ];

        // Validate the request
        $validator = Validator::make($clienteData, $rules);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Update the cliente
            $cliente->update($clienteData);

            return response()->json(['msg' => 'Cliente Empresa actualizado correctamente']);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json('Error en la consulta: ' . $e->getMessage(), 500);
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
                ->get();

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
                ->get();
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
