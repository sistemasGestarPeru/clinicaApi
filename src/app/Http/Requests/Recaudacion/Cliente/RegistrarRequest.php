<?php

namespace App\Http\Requests\Recaudacion\Cliente;

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
            'Nombres' => 'required|string',
            'Apellidos' => 'required|string',
            'Direccion' => 'required|string',
            'Celular' => [
                'required',
                'string',
                'size:9', // Esto asegura que tenga exactamente 9 caracteres
                'regex:/^9\d{8}$/' // Esto asegura que empiece con 9 y tenga 8 dígitos adicionales
            ],
            'Correo' => 'required|email',
            'CodigoTipoDocumento' => 'required|integer|min:1',
            'NumeroDocumento' => [
                'required',
                'string',
                'min:8',
                Rule::unique('personas')
                    ->where(function ($query) {
                        return $query->where('CodigoTipoDocumento', $this->input('CodigoTipoDocumento'))
                            ->where('vigente', 1);
                    }),
            ],
            'CodigoNacionalidad' => 'required|integer|min:1',
            'CodigoDepartamento' => 'required|integer|min:1',
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'CodigoTipoDocumento.required' => 'Debe seleccionar un tipo de documento.',
            'CodigoTipoDocumento.min' => 'Debe seleccionar un tipo de documento.',
            'Nombres.required' => 'El nombre es obligatorio.',
            'Apellidos.required' => 'El apellido es obligatorio.',
            'Celular.required' => 'El número de celular es obligatorio.',
            'Celular.regex' => 'El número de celular debe comenzar con 9 y contener nueve dígitos.',
            'Correo.required' => 'El correo electrónico es obligatorio.',
            'Correo.email' => 'El correo electrónico debe tener un formato válido.',
            'NumeroDocumento.required' => 'El número de documento es obligatorio.',
            'NumeroDocumento.min' => 'El número de documento debe tener al menos 8 dígitos.',
            'NumeroDocumento.unique' => 'El tipo y número de documento ya se encuentra registrado.',
            'CodigoNacionalidad.required' => 'Debe seleccionar una nacionalidad.',
            'CodigoNacionalidad.min' => 'Debe seleccionar una nacionalidad.',
            'CodigoDepartamento.required' => 'Debe seleccionar un departamento.',
            
        ];
    }
}
