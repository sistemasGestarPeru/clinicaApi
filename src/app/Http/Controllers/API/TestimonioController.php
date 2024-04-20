<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Testimonio\ActualizarTestimonioRequest;
use App\Http\Requests\Testimonio\GuardarTestimonioRequest;
use App\Http\Resources\TestimonioResource;
use App\Models\Testimonio;
use Illuminate\Http\Request;

class TestimonioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TestimonioResource::collection(Testimonio::all());
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GuardarTestimonioRequest $request)
    {
        return (new TestimonioResource(Testimonio::create($request->all())))
            ->additional(['msg' => 'Testimonio guardado correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Testimonio $testimonio)
    {
        return new TestimonioResource($testimonio);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ActualizarTestimonioRequest $request, Testimonio $testimonio)
    {
        $testimonio->nombre = $request->input('nombre') ?? '-';
        $testimonio->apellidoPaterno = $request->input('apellidoPaterno') ?? '-';
        $testimonio->apellidoMaterno = $request->input('apellidoMaterno') ?? '-';

        $testimonio->update($request->all());
        return (new TestimonioResource($testimonio))
            ->additional(['msg' => 'Testimonio actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Testimonio $testimonio)
    {
        $testimonio->delete();
        return (new TestimonioResource($testimonio))
            ->additional(['msg' => 'Testimonio eliminado correctamente']);
    }
}
