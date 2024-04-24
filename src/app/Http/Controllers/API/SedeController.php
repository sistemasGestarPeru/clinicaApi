<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sede\RegistrarSedeRequest;
use App\Http\Resources\SedeResource;
use Illuminate\Http\Request;
use App\Models\Sede;

class SedeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sedes = Sede::orderBy('nombre', 'asc')->get();
        // Retornar la colección de sedes utilizando SedeResource
        return SedeResource::collection($sedes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegistrarSedeRequest $request)
    {
        return (new SedeResource(Sede::create($request->all())))
            ->additional(['msg' => 'Sede guardada correctamente']);
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
