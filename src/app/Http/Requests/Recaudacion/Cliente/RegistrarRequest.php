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
            'CodigoTipoDocumento' => 'required|integer',
            'CodigoNacionalidad' => 'required|integer',
            'CodigoDepartamento' => 'required|integer',
        ];
    }
}
