<?php

namespace App\Http\Requests\Recaudacion\Pago;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
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
            'CodigoTrabajador' => 'required|integer|min:1',
            'CodigoMedioPago' => 'required|integer|min:1',
            'CodigoCaja' => 'required|integer|min:1',
            'Fecha' => 'required|date',
            'Monto' => 'required|numeric|min:0',
            'CodigoCuentaBancaria' => 'nullable|integer|min:0',
            'NumeroOperacion' => 'nullable|string|min:0',
        ];

        // if ($this->input('CodigoMedioPago') == 2) {
        //     $rules['CodigoCuentaBancaria'] = 'required|integer|min:1';
        //     $rules['NumeroOperacion'] = 'required|string|min:1';
        // }else{
        //     $rules['CodigoCuentaBancaria'] = 'nullable';
        //     $rules['NumeroOperacion'] = 'nullable';
        // }
        
        return $rules;
    }

    public function messages()
    {
        return [
            'CodigoTrabajador.required' => 'El trabajador es obligatorio.',
            'CodigoTrabajador.min' => 'El trabajador es obligatorio.',
            'CodigoMedioPago.required' => 'El medio de pago es obligatorio.',
            'CodigoMedioPago.min' => 'El medio de pago es obligatorio.',
            'CodigoCaja.required' => 'La caja es obligatoria.',
            'CodigoCaja.min' => 'La caja es obligatoria.',
            'Fecha.required' => 'La fecha es obligatoria.',
            'Fecha.date' => 'La fecha es obligatoria.',
            'Monto.required' => 'El monto es obligatorio.',
            'Monto.numeric' => 'El monto es obligatorio.',
            'Monto.min' => 'El monto es obligatorio y mayor a S/. 0.',
            'CodigoCuentaBancaria.required' => 'La cuenta bancaria es obligatoria.',
            'CodigoCuentaBancaria.min' => 'La cuenta bancaria es obligatoria.',
            'NumeroOperacion.required' => 'El número de operación es obligatorio.',
            'NumeroOperacion.min' => 'El número de operación es obligatorio.',
        ];
    }

}
