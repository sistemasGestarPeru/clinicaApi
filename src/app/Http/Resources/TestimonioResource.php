<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidoPaterno' => $this->apellidoPaterno,
            'apellidoMaterno' => $this->apellidoMaterno,
            'descripcion' => $this->descripcion,
            'sede_id' => $this->sede,
            'imagen' => $this->imagen,
            'vigente' => $this->vigente,
            'fecha_creacion' => $this->created_at ? $this->created_at->format('d-m-Y') : null,
            'fecha_actualizacion' => $this->updated_at ? $this->updated_at->format('d-m-Y') : null,
        ];
    }
}
