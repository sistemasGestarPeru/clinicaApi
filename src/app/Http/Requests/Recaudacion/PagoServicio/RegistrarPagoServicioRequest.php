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
            'IGV' => 'required|numeric|min:0',
            'CodigoProveedor' => 'required|integer|min:1',
            'TipoDocumento' => 'required|string|max:255'
        ];
    }

    public function messages()
    {
        return [
            'CodigoMotivoPago.required' => 'El campo Motivo de Pago es obligatorio.',
            'CodigoMotivoPago.integer' => 'El campo Motivo de Pago es obligatorio.',
            'CodigoMotivoPago.min' => 'Debe seleccionar un Motivo de Pago.',
            'Descripcion.required' => 'El campo Descripción es obligatorio.',
            'Descripcion.string' => 'El campo Descripción debe ser una cadena de texto.',
            'Descripcion.max' => 'El campo Descripción no debe exceder los 255 caracteres.',
            'IGV.required' => 'El campo IGV es obligatorio.',
            'IGV.numeric' => 'El campo IGV debe ser un número.',
            'IGV.min' => 'El campo IGV debe ser mayor o igual a 0.',
            'CodigoProveedor.required' => 'El campo Proveedor es obligatorio.',
            'CodigoProveedor.integer' => 'El campo Proveedor es obligatorio.',
        ];
    }
}
