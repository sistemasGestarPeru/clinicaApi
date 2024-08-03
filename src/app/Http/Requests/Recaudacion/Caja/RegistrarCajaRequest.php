<?php

namespace App\Http\Requests\Recaudacion\Caja;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCajaRequest extends FormRequest
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
            'Estado' => 'required|string',
            'CodigoSede' => 'required|integer',
            'CodigoTrabajador' => 'required|integer'
        ];
    }
}
