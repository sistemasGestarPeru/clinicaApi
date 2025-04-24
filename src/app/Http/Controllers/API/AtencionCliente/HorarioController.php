<?php

namespace App\Http\Controllers\API\AtencionCliente;

use App\Http\Controllers\Controller;
use App\Models\AtencionCliente\Horario;
use Illuminate\Http\Request;

class HorarioController extends Controller
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

    public function listarHorarios(){
        try{
            $horarios = Horario::all()->where('Vigente', 1);
            return response()->json($horarios, 200);
        }catch(\Exception $e){
            return response()->json([
                'msg' => 'Error al listar los horarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registrarHorario(Request $request){
        try{
            Horario::create($request->all());
            return response()->json([
                'msg' => 'Horario registrado correctamente.'
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'msg' => 'Error al registrar el horario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
