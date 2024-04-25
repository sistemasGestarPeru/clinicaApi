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
            "id" => "required|integer",
            'nombre' => 'required|string|max:31',
            'apellidoPaterno' => 'required|string|max:63',
            'apellidoMaterno' => 'required|string|max:63',
            'genero' => 'nullable|boolean',
            'descripcion' => 'required|string|max:500',
            // 'CMP' => "'nullable|unique:medicos,CMP," . $this->route('medico')->id,
            // 'RNE' => "'nullable|unique:medicos,RNE," . $this->route('medico')->id,
            // 'CBP' => "'nullable|unique:medicos,CBP," . $this->route('medico')->id,
            'tipo' => 'nullable|boolean',
            'sede_id' => 'nullable|integer|exists:sedes,id',
            'vigente' => 'nullable|boolean',
        ];
    }
}
