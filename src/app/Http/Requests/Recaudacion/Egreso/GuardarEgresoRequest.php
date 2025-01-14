<?php

namespace App\Http\Requests\Recaudacion\Egreso;

use Illuminate\Foundation\Http\FormRequest;

class GuardarEgresoRequest extends FormRequest
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
            'Fecha' => 'required|date',
            'Monto' => 'required|numeric|min:0.01',
            'CodigoCaja' => 'required|integer|min:1',
            'CodigoTrabajador' => 'required|integer|min:1',
            'CodigoMedioPago' => 'required|integer|min:1',
            //'CodigoCuentaOrigen' => 'required|integer|min:1', //Modificar luego
        ];
    }

    public function messages(): array
    {
        return [
            'Fecha.required' => 'El campo Fecha es obligatorio.',
            'Fecha.date' => 'El campo Fecha debe ser una fecha válida.',

            'Monto.required' => 'El campo Monto es obligatorio.',
            'Monto.numeric' => 'El campo Monto debe ser un número.',
            'Monto.min' => 'El campo Monto debe ser mayor a S/ 0.',

            'CodigoCaja.required' => 'El campo Caja es obligatorio.',
            'CodigoCaja.integer' => 'No se encontró Caja Abierta.',
            'CodigoCaja.min' => 'No se encontró Caja Abierta.',

            'CodigoTrabajador.required' => 'No se encontró Trabajador.',
            'CodigoTrabajador.integer' => 'No se encontró Trabajador.',
            'CodigoTrabajador.min' => 'No se encontró Trabajador.',

            'CodigoMedioPago.required' => 'Debe seleccionar un Medio de Pago.',
            'CodigoMedioPago.integer' => 'Debe seleccionar un Medio de Pago.',
            'CodigoMedioPago.min' => 'Debe seleccionar un Medio de Pago.',
        ];
    }
}
