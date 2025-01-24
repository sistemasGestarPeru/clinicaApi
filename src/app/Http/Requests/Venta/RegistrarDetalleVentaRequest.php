<?php

namespace App\Http\Requests\Venta;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDetalleVentaRequest extends FormRequest
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
            //detalleVenta
            'detalleVenta' => ['required', 'array', 'min:1'], // Validar que detalleVenta sea un arreglo y tenga al menos un elemento
            'detalleVenta.*.Numero' => ['required', 'integer', 'min:1'],
            'detalleVenta.*.Descripcion' => ['required', 'string'],
            'detalleVenta.*.Cantidad' => ['required', 'integer', 'min:1'],
            'detalleVenta.*.MontoTotal' => ['required', 'numeric', 'min:1'],
            'detalleVenta.*.MontoIGV' => ['required', 'numeric', 'min:0'],
            'detalleVenta.*.CodigoProducto' => ['required', 'integer', 'min:1'],
            'detalleVenta.*.CodigoTipoGravado' => ['required', 'integer', 'min:1'],

        ];
    }
}
