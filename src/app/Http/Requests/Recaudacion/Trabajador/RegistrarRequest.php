<?php

namespace App\Http\Requests\Recaudacion\Trabajador;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarRequest extends FormRequest
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
            'Codigo' => 'required|integer',
            'CorreoCorporativo' => 'required|email',
            'FechaNacimiento' => 'required|date',
        ];
    }
}
