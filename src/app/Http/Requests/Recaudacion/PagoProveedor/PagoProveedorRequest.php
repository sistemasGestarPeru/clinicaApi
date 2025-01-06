<?php

namespace App\Http\Requests\Recaudacion\PagoProveedor;

use Illuminate\Foundation\Http\FormRequest;

class PagoProveedorRequest extends FormRequest
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
            // 'CodigoCuota' => 'required|integer|min:1',
            'CodigoProveedor' => 'required|integer|min:1',
            'TipoMoneda' => 'required|string',
            // 'MontoMonedaExtranjera' => 'required|numeric|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            // 'CodigoCuota.required' => 'El campo Cuota es obligatorio.',
            // 'CodigoCuota.integer' => 'El campo Cuota debe ser un número entero.',
            // 'CodigoCuota.min' => 'El campo Cuota debe ser mayor a 0.',

            'CodigoProveedor.required' => 'El campo Proveedor es obligatorio.',
            'CodigoProveedor.integer' => 'Debe seleccionar un Proveedor.',
            'CodigoProveedor.min' => 'Debe seleccionar un Proveedor.',

            'TipoMonedad.required' => 'El campo Moneda es obligatorio.',
            'TipoMonedad.string' => 'Error con el Tipo de Moneda.',
            // 'MontoMonedaExtranjera.required' => 'El campo Monto Moneda Extranjera es obligatorio.',
            // 'MontoMonedaExtranjera.numeric' => 'El campo Monto Moneda Extranjera debe ser un número.',
            // 'MontoMonedaExtranjera.min' => 'El campo Monto Moneda Extranjera debe ser mayor a 0.',
        ];
    }
}
