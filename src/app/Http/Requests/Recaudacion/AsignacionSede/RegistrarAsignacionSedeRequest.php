<?php

namespace App\Http\Requests\Recaudacion\AsignacionSede;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarAsignacionSedeRequest extends FormRequest
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
        $rules = [
            'CodigoSede' => 'required|integer|min:1',
            'CodigoTrabajador' => 'required|integer|min:1',
            'FechaInicio' => 'required|date',
            'FechaFin' => 'nullable|date|after_or_equal:FechaInicio',
        ];

        if($this->input('FechaFin') !== null) {
            $rules['FechaFin'] = 'required|date|after_or_equal:FechaInicio';
        }
        return $rules;
    }
}
