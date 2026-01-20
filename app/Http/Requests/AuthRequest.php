<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
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
        $channelType = $this->header('X-Auth-Channel-Type', 'web');

        $rules = [];

        switch ($channelType) {
            case 'telegram':
                $rules = $this->getTelegramRules();
                break;
            case 'web':
                $rules = $this->getWebRules();
                break;
            case 'api':
                $rules = $this->getApiRules();
                break;
            case 'n8n_webhook':
                $rules = $this->getN8NRules();
                break;
            default:
                $rules = $this->getWebRules();
        }

        return $rules;
    }

    private function getWebRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:100']
        ];
    }

    private function getTelegramRules(): array
    {
        return [
            'telegram_id' => ['required', 'integer', 'min:1'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'chat_id' => ['required', 'integer'],
            'language_code' => ['nullable', 'string', 'size:2']
        ];
    }

    private function getApiRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'string', 'max:255']
        ];
    }

    private function getN8NRules(): array
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
            'metadata' => ['nullable', 'array']
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email обязателен для заполнения',
            'email.email' => 'Неверный формат email',
            'password.required' => 'Пароль обязателен для заполнения',
            'password.min' => 'Пароль должен содержать минимум :min символов',
            'telegram_id.required' => 'Telegram ID обязателен',
            'first_name.required' => 'Имя обязательно для Telegram авторизации',
            'chat_id.required' => 'Chat ID обязателен для Telegram авторизации',
            'token.required' => 'Токен обязателен для N8N webhook',
            'token.size' => 'Токен должен содержать ровно :size символов'
        ];
    }

    public function prepareForValidation(): void
    {
        // Подготовка данных для валидации
        if ($this->header('X-Auth-Channel-Type') === 'telegram') {
            $this->merge([
                'channel_type' => 'telegram'
            ]);
        }
    }
}
