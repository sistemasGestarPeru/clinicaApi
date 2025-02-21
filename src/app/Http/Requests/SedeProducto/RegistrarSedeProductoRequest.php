<?php

namespace App\Http\Requests\SedeProducto;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarSedeProductoRequest extends FormRequest
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
            'sedeProductos' => ['required', 'array', 'min:1'], 
            'sedeProductos.*.CodigoSede' => ['required', 'integer', 'min:1'],
            'sedeProductos.*.CodigoProducto' => ['required', 'integer', 'min:1'],
            'sedeProductos.*.CodigoTipoGravado' => ['required', 'integer', 'min:1'],
            'sedeProductos.*.Stock' => ['required', 'integer', 'min:0'],
            'sedeProductos.*.Precio' => ['required', 'numeric', 'min:0'],

        ];
    }
}
