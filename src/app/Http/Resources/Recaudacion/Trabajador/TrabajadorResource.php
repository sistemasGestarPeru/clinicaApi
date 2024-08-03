<?php

namespace App\Http\Resources\Recaudacion\Trabajador;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrabajadorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'Codigo' => $this->Codigo,
            'CorreoCorporativo' => $this->CorreoCorporativo,
            'FechaNacimiento' => $this->FechaNacimiento,
            'Vigente' => $this->Vigente,
        ];
    }
}
