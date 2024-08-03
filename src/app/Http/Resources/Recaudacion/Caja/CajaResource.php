<?php

namespace App\Http\Resources\Recaudacion\Caja;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CajaResource extends JsonResource
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
            'FechaInicio' => $this->FechaInicio,
            'FechaFin' => $this->FechaFin,
            'Estado' => $this->Estado,
            'CodigoSede' => $this->CodigoSede,
            'CodigoTrabajador' => $this->CodigoTrabajador
        ];
    }
}
