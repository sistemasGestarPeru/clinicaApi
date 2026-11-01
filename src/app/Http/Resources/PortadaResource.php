<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortadaResource extends JsonResource
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
            'imagenEsc' => $this->imagenEsc,
            'imagenCel' => $this->imagenCel,
            'TextoBtn' => $this->TextoBtn,
            'UrlBtn' => $this->UrlBtn,
            'TextoBtn2' => $this->TextoBtn2,
            'UrlBtn2' => $this->UrlBtn2,
            'vigente' => $this->vigente,
            'Titulo' => $this->Titulo,
            'Descripcion' => $this->Descripcion
        ];
    }
}
