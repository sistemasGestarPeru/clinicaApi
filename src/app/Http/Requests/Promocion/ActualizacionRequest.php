<?php

namespace App\Http\Requests\Promocion;

use Illuminate\Foundation\Http\FormRequest;

class ActualizacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "id" => "required|integer",
            "titulo" => "required|string",
            "fecha_inicio" => "required|date",
            "fecha_fin" => "required|date",
            "descripcion" => "nullable|string", 
        ];
    }
}
