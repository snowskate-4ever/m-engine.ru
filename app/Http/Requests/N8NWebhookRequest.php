<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class N8NWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->verifyN8NSignature();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'workflow_id' => ['nullable', 'string', 'max:255'],
            'execution_id' => ['nullable', 'string', 'max:255'],
            'node_type' => ['nullable', 'string', 'max:100'],
            'callback_url' => ['nullable', 'url'],
            'metadata' => ['nullable', 'array'],
            'source_system' => ['nullable', 'string', 'max:100']
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Токен авторизации обязателен',
            'token.size' => 'Токен должен содержать ровно :size символов',
            'email.required' => 'Email обязателен',
            'email.email' => 'Неверный формат email',
            'password.min' => 'Пароль должен содержать минимум :min символов',
            'callback_url.url' => 'Callback URL должен быть корректным URL'
        ];
    }

    private function verifyN8NSignature(): bool
    {
        $signature = $this->header('X-N8N-Signature');
        $webhookSecret = config('services.n8n.webhook_secret');

        if (!$signature || !$webhookSecret) {
            return false;
        }

        $payload = $this->getContent();
        $expected = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function prepareForValidation(): void
    {
        // Если пароль не передан, генерируем случайный
        if (!$this->has('password') || empty($this->input('password'))) {
            $this->merge([
                'password' => \Illuminate\Support\Str::random(12)
            ]);
        }
    }
}
