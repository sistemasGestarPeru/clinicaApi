<?php

namespace App\Http\Requests\Recaudacion\Cliente;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "Nombres" => "required|string",
            "Apellidos" => "required|string",
            "Direccion" => "required|string",
            "Celular" => "required|string",
            "Correo" => "required|string",
            "NumeroDocumento" => "required|string",
            "CodigoTipoDocumento" => "required|integer",
            "CodigoNacionalidad" => "required|integer",
            "CodigoDepartamento" => "required|integer",
        ];
    }
}
