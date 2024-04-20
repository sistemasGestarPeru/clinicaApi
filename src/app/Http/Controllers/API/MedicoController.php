<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medico\ActualizarMedicoRequest;
use App\Http\Requests\Medico\GuardarMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Models\Medico;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $tipo = $request->input('tipo'); // Obtener el parámetro 'tipo' de la solicitud

        // Verificar si se proporcionó el parámetro 'tipo' y filtrar los médicos en consecuencia
        if ($tipo !== null) {
            $medicos = Medico::where('tipo', $tipo)->get();
        } else {
            // Si no se proporciona el parámetro 'tipo', mostrar todos los médicos
            $medicos = Medico::all();
        }

        return MedicoResource::collection($medicos);
    }

    public function listarGinecologos()
    {
        return MedicoResource::collection(Medico::where('tipo', 0)->get());
    }

    public function listarBiologos()
    {
        return MedicoResource::collection(Medico::where('tipo', 1)->get());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(GuardarMedicoRequest $request)
    {
        return (new MedicoResource(Medico::create($request->all())))
            ->additional(['msg' => 'Medico guardado correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Medico $medico)
    {
        return new MedicoResource($medico);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActualizarMedicoRequest $request, Medico $medico)
    {
        $medico->update($request->all());
        return (new MedicoResource($medico))
            ->additional(['msg' => 'Medico actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Medico $medico)
    {
        $medico->delete();
        return (new MedicoResource($medico))
            ->additional(['msg' => 'Medico eliminado correctamente']);
    }
}
