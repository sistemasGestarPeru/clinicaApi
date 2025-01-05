<?php

namespace App\Http\Resources\Recaudacion\ContratoLaboral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratoLaboralResource extends JsonResource
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
            'Tipo' => $this->Tipo,
            'Tiempo' => $this->Tiempo,
            'SueldoBase' => $this->SueldoBase,
            'CodigoEmpresa' => $this->CodigoEmpresa,
            'CodigoTrabajador' => $this->CodigoTrabajador,
        ];
    }
}
