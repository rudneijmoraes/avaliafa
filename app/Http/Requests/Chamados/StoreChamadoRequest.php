<?php

namespace App\Http\Requests\Chamados;

use Illuminate\Foundation\Http\FormRequest;

class StoreChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'client_system_id' => 'nullable|exists:client_systems,id',
            'subject' => 'required|string|max:180',
            'message' => 'required|string|max:5000',
            'priority' => 'required|in:low,normal,high',
        ];
    }
}
