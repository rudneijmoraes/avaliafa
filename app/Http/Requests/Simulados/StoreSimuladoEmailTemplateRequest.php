<?php

namespace App\Http\Requests\Simulados;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimuladoEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isProfessor();
    }

    public function rules(): array
    {
        return [
            'client_system_id' => 'nullable|exists:client_systems,id',
            'name' => 'required|string|max:120',
            'subject' => 'required|string|max:180',
            'html_body' => 'required|string',
            'active' => 'nullable|boolean',
        ];
    }
}
