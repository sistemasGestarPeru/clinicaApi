<?php

namespace App\Http\Requests\Recaudacion\Trabajador;

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
            'CorreoCoorporativo' => [
                'nullable',
                'email',
                Rule::unique('trabajadors', 'CorreoCoorporativo')->where('Vigente', 1)
            ],
            'FechaNacimiento' => 'required|date',
            'CodigoSistemaPensiones' => 'required|integer',
        ];
    }

    public function messages(): array{
        return [
            'CorreoCoorporativo.email' => 'El correo corporativo debe ser un correo válido.',
            'CorreoCoorporativo.unique' => 'El correo corporativo ya está en uso.',
            'FechaNacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'FechaNacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'CodigoSistemaPensiones.required' => 'El código del sistema de pensiones es obligatorio.',
        ];
    }
}

