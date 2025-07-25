<?php

namespace App\Http\Requests\Recaudacion\Cliente;

use Illuminate\Foundation\Http\FormRequest;


class ActualizarRequest extends FormRequest
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
            // 'Nombres' => 'required|string',
            // 'Apellidos' => 'required|string',
            'Codigo' => 'required|integer',
            'Direccion' => 'required|string',
            'Celular' => [
                'required',
                'string',
                'size:9', // Esto asegura que tenga exactamente 9 caracteres
                'regex:/^9\d{8}$/' // Esto asegura que empiece con 9 y tenga 8 dígitos adicionales
            ],

            'Correo' => 'required|email',
            // 'CodigoTipoDocumento' => 'required|integer',
            // 'NumeroDocumento' => [
            //     'required',
            //     'string',
            //     'min:8',
            //     Rule::unique('personas', 'NumeroDocumento')
            //         ->where(function ($query) {
            //             $query->where('CodigoTipoDocumento', $this->input('CodigoTipoDocumento'))
            //                 ->where('Vigente', 1); // Solo considera registros activos
            //         })
            //         ->ignore($this->input('Codigo'), 'Codigo'), // Ignora el registro actual
            // ],
             'CodigoNacionalidad' => 'required|integer',
            // 'CodigoDepartamento' => 'required|integer',
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'CodigoTipoDocumento.required' => 'Debe seleccionar un tipo de documento.',
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

        ];
    }
}
