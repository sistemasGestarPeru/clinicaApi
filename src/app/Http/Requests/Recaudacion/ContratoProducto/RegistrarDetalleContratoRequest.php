<?php

namespace App\Http\Requests\Recaudacion\ContratoProducto;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDetalleContratoRequest extends FormRequest
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
            'detalleContrato' => ['required', 'array', 'min:1'], // Validar que detalleContrato sea un arreglo y tenga al menos un elemento
            'detalleContrato.*.CodigoProducto' => ['required', 'integer', 'min:1'],
            'detalleContrato.*.Descripcion' => ['required', 'string', 'min:1'],
            'detalleContrato.*.Cantidad' => ['required', 'integer', 'min:1'],
            'detalleContrato.*.MontoTotal' => ['required', 'numeric', 'min:1'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'detalleContrato.required' => 'Debe incluir al menos un producto en el contrato.',
            'detalleContrato.array' => 'El detalle del contrato debe ser un arreglo válido.',
            'detalleContrato.min' => 'Debe incluir al menos un producto en el contrato.',
    
            'detalleContrato.*.CodigoProducto.required' => 'Debe seleccionar un producto.',
            'detalleContrato.*.CodigoProducto.integer' => 'El código del producto debe ser un número entero.',
            'detalleContrato.*.CodigoProducto.min' => 'El código del producto debe ser válido.',
    
            'detalleContrato.*.Descripcion.required' => 'Debe ingresar una descripción.',
            'detalleContrato.*.Descripcion.string' => 'La descripción debe ser un texto.',
            'detalleContrato.*.Descripcion.min' => 'La descripción no puede estar vacía.',
    
            'detalleContrato.*.Cantidad.required' => 'Debe especificar una cantidad.',
            'detalleContrato.*.Cantidad.integer' => 'La cantidad debe ser un número entero.',
            'detalleContrato.*.Cantidad.min' => 'La cantidad debe ser mayor a 0.',
    
            'detalleContrato.*.MontoTotal.required' => 'Debe especificar el monto total.',
            'detalleContrato.*.MontoTotal.numeric' => 'El monto total debe ser un número.',
            'detalleContrato.*.MontoTotal.min' => 'El monto total debe ser mayor a 0.',
        ];
    }
    
}
