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
            'Celular' => 'required|string|max:9|min:9',
            'Correo' => 'required|email',
            'NumeroDocumento' => [
                'required',
                'string',
                'min:5',
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
