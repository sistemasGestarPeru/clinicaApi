<?php

namespace App\Http\Resources\Recaudacion\Cliente;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Cliente extends JsonResource
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
            'Nombres' => $this->Nombres,
            'Apellidos' => $this->Apellidos,
            'Direccion' => $this->Direccion,
            'Celular' => $this->Celular,
            'Correo' => $this->Correo,
            'NumeroDocumento' => $this->NumeroDocumento,
            'CodigoTipoDocumento' => $this->CodigoTipoDocumento,
            'CodigoNacionalidad' => $this->CodigoNacionalidad,
            'CodigoDepartamento' => $this->CodigoDepartamento
        ];
    }
}
