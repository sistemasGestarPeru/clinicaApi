<?php

namespace App\Http\Requests\Recaudacion\ContratoProducto;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarContratoProductoRequest extends FormRequest
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
            'CodigoPaciente' => 'required|integer|min:1',
            'CodigoSede' => 'required|integer|min:1',
            'CodigoTrabajador' => 'required|integer|min:1',
            'Total' => 'required|numeric|min:1',
            'CodigoMedico' => 'required|integer|min:1',
        ];
    }
    
    public function messages(): array
    {
        return [
            'CodigoPaciente.required' => 'Debe seleccionar un Paciente.',
            'CodigoPaciente.min' => 'Debe seleccionar un Paciente válido.',
            'CodigoSede.required' => 'Debe seleccionar una Sede.',
            'CodigoTrabajador.required' => 'Trabajador no válido.',
            'CodigoTrabajador.min' => 'Trabajador no válido.',
            'Total.min'=> 'Monto debe ser mayor a S/. 0.',
            'Total.required'=> 'Monto no válido.',
            'Total.numeric'=> 'Monto no válido.',
            'CodigoMedico.required' => 'Debe seleccionar un Médico.',
            'CodigoMedico.min' => 'Médico no válido.',
            
        ];
    }
    
}
