<?php

namespace App\Http\Requests\Recaudacion\PagoServicio;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoServicioRequest extends FormRequest
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
            'CodigoMotivoPago' => 'required|integer|min:1',
            'Descripcion' => 'required|string|max:255',

        ];
    }

    public function messages()
    {
        return [
            'CodigoMotivoPago.required' => 'El campo Motivo de Pago es obligatorio.',
            'CodigoMotivoPago.integer' => 'El campo Motivo de Pago es obligatorio.',
            'CodigoMotivoPago.min' => 'Debe seleccionar un Motivo de Pago.',
        ];
    }
}
