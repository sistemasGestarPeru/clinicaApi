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
            'Comentario' => $this->Comentario,
            'Link' => $this->Link,
        ];
    }
}
