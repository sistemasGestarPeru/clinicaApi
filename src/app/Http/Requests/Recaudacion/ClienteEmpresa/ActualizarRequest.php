<?php

namespace App\Http\Requests\Recaudacion\ClienteEmpresa;

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
            "Codigo" => "required|integer",
            "RazonSocial" => "required|string",
            "RUC" => "required|string",
            "Direccion" => "required|string",
            "CodigoDepartamento" => "required|integer"
        ];
    }
}
