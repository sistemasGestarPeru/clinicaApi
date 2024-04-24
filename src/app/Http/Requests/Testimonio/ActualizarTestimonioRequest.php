<?php

namespace App\Http\Requests\Testimonio;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarTestimonioRequest extends FormRequest
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
            "id" => "required|integer",
            "nombre" => "nullable|string",
            "apellidoPaterno" => "nullable|string",
            "apellidoMaterno" => "nullable|string",
            "descripcion" => "nullable|string",
            "sede_id" => "nullable|integer",
            "imagen" => "nullable|image",
            "vigente" => "nullable|boolean",
        ];
    }
}
