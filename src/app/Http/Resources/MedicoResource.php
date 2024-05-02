<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
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
            'genero' => $this->genero,
            'imagen' => $this->imagen,
            'linkedin' => $this->linkedin,
            'descripcion' => $this->descripcion,
            'CMP' => $this->CMP,
            'RNE' => $this->RNE,
            'CBP' => $this->CBP,
            'tipo' => $this->tipo,
            'sede_id' => $this->sede,
            'vigente' => $this->vigente,
        ];
    }
}
