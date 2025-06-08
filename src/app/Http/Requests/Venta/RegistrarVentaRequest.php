<?php

namespace App\Http\Requests\Venta;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarVentaRequest extends FormRequest
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
            'CodigoTipoDocumentoVenta' => 'required|integer|min:1',
            'CodigoSede' => 'required|integer|min:1',
            'Serie' => 'required|string',
            'Numero' => 'required|integer|min:1',
            'CodigoTrabajador' => 'required|integer|min:1',
            'TotalGravado' => 'required|numeric',
            'TotalExonerado' => 'required|numeric',
            'TotalInafecto' => 'required|numeric',
            'TotalGratis' => 'nullable|numeric',
            'IGVTotal' => 'required|numeric',
            'MontoTotal' => 'required|numeric',
            'MontoPagado' => 'required|numeric',
            'CodigoContratoProducto' => 'nullable|integer|min:1',
            'CodigoCaja' => 'required|integer|min:1',
            // 'CodigoAutorizador' => 'nullable|integer|min:1',
            // 'CodigoMedico' => 'required|integer|min:0',
            'CodigoPaciente' => 'required|integer|min:1',
        ];

        // if($this->input('CodigoPersona') == 0){
        //     $rules['CodigoClienteEmpresa'] = 'required|integer|min:1';
        // }else{
        //     $rules['CodigoPersona'] = 'required|integer|min:1';
        // }

        return $rules;
    }

    // public function messages()
    // {
    //     return [

    //     ];
    // }
}
