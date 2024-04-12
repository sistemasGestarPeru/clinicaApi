<?php

namespace App\Http\Requests\Medico;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarMedicoRequest extends FormRequest
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
            'nombre' => "required",
            'apellidoPaterno' => "required",
            'apellidoMaterno' => "required",
            'genero' => "required",
            'imagen' => "required",
            'descripcion' => "required",
            'CMP' => "unique:medicos,CMP," . $this->route('medico')->id,
            'RNE' => "unique:medicos,RNE," . $this->route('medico')->id,
            'CBP' => "unique:medicos,CBP," . $this->route('medico')->id,
            'tipo' => 'required|boolean',
        ];
    }
}
