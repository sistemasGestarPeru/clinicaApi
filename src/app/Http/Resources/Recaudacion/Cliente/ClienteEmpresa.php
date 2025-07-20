<?php

namespace App\Http\Resources\Recaudacion\Cliente;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteEmpresa extends JsonResource
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
            'RazonSocial' => $this->RazonSocial,
            'RUC' => $this->RUC,
            'Direccion' => $this->Direccion,
            // 'CodigoDepartamento' => $this->CodigoDepartamento,
            'Vigente' => $this->Vigente
        ];
    }
}
