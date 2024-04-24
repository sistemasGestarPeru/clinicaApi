<?php

namespace App\Http\Requests\Medico;

use Illuminate\Foundation\Http\FormRequest;

class GuardarMedicoRequest extends FormRequest
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
            'nombre' => 'required|string|max:31',
            'apellidoPaterno' => 'required|string|max:63',
            'apellidoMaterno' => 'required|string|max:63',
            'genero' => 'required|boolean',
            
            'descripcion' => 'required|string|max:500',
            'CMP' => 'nullable|string|max:10|unique:medicos',
            'RNE' => 'nullable|string|max:10|unique:medicos',
            'CBP' => 'nullable|string|max:10|unique:medicos',
            'sede_id' => 'required|integer',
            'tipo' => 'required|boolean',
        ];
    }
}
