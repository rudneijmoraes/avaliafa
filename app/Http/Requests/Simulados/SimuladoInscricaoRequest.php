<?php

namespace App\Http\Requests\Simulados;

use Illuminate\Foundation\Http\FormRequest;

class SimuladoInscricaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'phone' => preg_replace('/\D/', '', (string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:120',
            'last_name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|min:10|max:20',
            'cpf' => 'required|string|size:11',
            'password' => 'required|string|min:6|max:64|confirmed',
            'simulado_slug' => 'nullable|string|max:180',
            'robot_confirm' => 'required|accepted',
            'captcha_token' => 'required|string|max:120',
            'captcha_answer' => 'required|integer|min:0|max:999',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->isValidCpf((string) $this->input('cpf'))) {
                $validator->errors()->add('cpf', 'CPF inválido.');
            }

            $token = trim((string) $this->input('captcha_token'));
            $expected = (int) session()->get('simulado_captcha.'.$token, -1);
            $answer = (int) $this->input('captcha_answer', -1);

            if ($token === '' || $expected < 0 || $answer !== $expected) {
                $validator->errors()->add('captcha_answer', 'Validação anti-robô inválida.');
            }

            if ($token !== '') {
                session()->forget('simulado_captcha.'.$token);
            }
        });
    }

    private function isValidCpf(string $cpf): bool
    {
        if (! preg_match('/^\d{11}$/', $cpf)) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($c = 0; $c < $t; $c++) {
                $sum += ((int) $cpf[$c]) * (($t + 1) - $c);
            }

            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
