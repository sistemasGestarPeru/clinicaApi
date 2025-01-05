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
            'CodigoPaciente' => 'required_without:CodigoEmpresa|integer',
            'CodigoEmpresa' => 'required_without:CodigoPaciente|integer',
            'CodigoSede' => 'required|integer|min:1',
            'CodigoTrabajador' => 'required|integer|min:1',
            'Total' => 'required|numeric|min:1'
        ];
    }
    
    public function messages(): array
    {
        return [
            'CodigoPaciente.required_without' => 'Debe seleccionar un Paciente.',
            'CodigoEmpresa.required_without' => 'Debe seleccionar una Empresa .',
            'CodigoPaciente.min' => 'Debe seleccionar un Paciente válido.',
            'CodigoEmpresa.min' => 'Debe seleccionar una Empresa válida.',
            'CodigoSede.required' => 'Debe seleccionar una Sede.',
            'CodigoTrabajador.required' => 'Trabajador no válido.',
            'CodigoTrabajador.min' => 'Trabajador no válido.',
            'Total.min'=> 'Monto debe ser mayor a S/. 0.',
            'Total.required'=> 'Monto no válido.',
            'Total.numeric'=> 'Monto no válido.',
            
        ];
    }
    
}
