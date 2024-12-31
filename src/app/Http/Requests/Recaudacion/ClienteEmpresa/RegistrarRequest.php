<?php

namespace App\Http\Requests\Recaudacion\ClienteEmpresa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarRequest extends FormRequest
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
            'RazonSocial' => 'required|string',
            'RUC' => [
                'required',
                'string',
                'size:11',
                Rule::unique('clienteempresa')
                    ->where(function ($query) {
                        return $query->where('vigente', 1);
                    }),
            ],
            'Direccion' => 'required|string',
            'CodigoDepartamento' => 'required|integer'
        ];
    }

    public function messages(): array
    {
        return [
            'RazonSocial.required' => 'La Razón Social es obligatoria.',
            'RUC.required' => 'El RUC es obligatorio.',
            'RUC.size' => 'El RUC debe contener 11 dígitos.',
            'RUC.unique' => 'El RUC ya se encuentra registrado.',
            'Direccion.required' => 'La Dirección es obligatoria.',
            'CodigoDepartamento.required' => 'El Departamento es obligatorio.'
        ];
    }
}
