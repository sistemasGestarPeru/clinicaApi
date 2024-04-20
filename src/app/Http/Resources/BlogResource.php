<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
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
            'Titulo' => $this->Titulo,
            'Fecha' => $this->Fecha,
            'Imagen' => $this->Imagen,
            'Descripcion' => $this->Descripcion,
            'vigente' => $this->vigente,
            'fecha_creacion' => $this->created_at ? $this->created_at->format('d-m-Y') : null,
            'fecha_actualizacion' => $this->updated_at ? $this->updated_at->format('d-m-Y') : null,
        ];
    }
}
