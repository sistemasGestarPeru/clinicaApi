<?php

namespace App\Http\Requests\Testimonio;

use Illuminate\Foundation\Http\FormRequest;

class GuardarTestimonioRequest extends FormRequest
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
            "nombre" => "nullable|string",
            "apellidoPaterno" => "nullable|string",
            "apellidoMaterno" => "nullable|string",
            "descripcion" => "required|string",
            "sede_id" => "required|integer",
            "fecha" => "required|date",
        ];
    }
}
