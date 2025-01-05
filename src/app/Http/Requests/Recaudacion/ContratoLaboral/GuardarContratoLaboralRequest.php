<?php

namespace App\Http\Requests\Recaudacion\ContratoLaboral;

use Illuminate\Foundation\Http\FormRequest;

class GuardarContratoLaboralRequest extends FormRequest
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
            'CodigoEmpresa' => 'required|integer|min:1',
            'Tipo' => 'required|string',
            'Tiempo' => 'required|string|in:D,I',
            'SueldoBase' => 'required|numeric|min:1',
            'CodigoTrabajador' => 'required|integer|min:1',
        ];

        if ($this->input('Tiempo') === 'D') {
            $rules['FechaInicio'] = 'required|date';
            $rules['FechaFin'] = 'required|date|after_or_equal:FechaInicio';
        } elseif ($this->input('Tiempo') === 'I') {
            $rules['FechaInicio'] = 'required|date';
            $rules['FechaFin'] = 'nullable|date';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'CodigoEmpresa.required' => 'Debe seleccionar una empresa.',
            'CodigoEmpresa.min' => 'Debe seleccionar una empresa.',
            'Tipo.required' => 'El tipo de contrato es obligatorio.',
            'Tipo.string' => 'El tipo de contrato no es válido.',
            'Tiempo.required' => 'El tiempo de contrato es obligatorio.',
            'Tiempo.string' => 'El tiempo de contrato no es válido.',
            'Tiempo.in' => 'El tiempo de contrato debe ser "D" o "I".',
            'FechaInicio.required' => 'La fecha de inicio es obligatoria.',
            'FechaInicio.date' => 'La fecha de inicio no es válida.',
            'FechaFin.required' => 'La fecha de fin es obligatoria para contratos de duración definida.',
            'FechaFin.date' => 'La fecha de fin no es válida.',
            'FechaFin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'SueldoBase.required' => 'El sueldo base es obligatorio.',
            'SueldoBase.numeric' => 'El sueldo base no es válido.',
            'SueldoBase.min' => 'El sueldo base no es válido.',
            'CodigoTrabajador.required' => 'Debe seleccionar un trabajador.',
            'CodigoTrabajador.min' => 'Debe seleccionar un trabajador.',
        ];
    }
}
