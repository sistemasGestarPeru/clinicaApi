<?php

namespace App\Http\Requests\Promocion;

use Illuminate\Foundation\Http\FormRequest;

class RegistroRequest extends FormRequest
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
            "titulo" => "required|string",
            "fecha_inicio" => "required|date",
            "fecha_fin" => "required|date",
            "descripcion" => "nullable|string",
            "sedes" => "required|string",
        ];
    }
}
