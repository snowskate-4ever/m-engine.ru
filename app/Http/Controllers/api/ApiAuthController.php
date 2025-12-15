<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Services\api\ApiService;
use Illuminate\Support\Facades\Auth;

class ApiAuthController extends Controller
{
    public function login(Request $request)
    {
        $success = true;
        $errors = [];
        $data = [];
        $status = 200;

        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Поле "Email / Телефон" обязательно для заполнения.',
            'email.string' => 'Поле "Email / Телефон" должно быть строкой.',
            'password.required' => 'Поле "Пароль" обязательно для заполнения.',
            'password.string' => 'Поле "Пароль" должно быть строкой.',
        ]);

        if ($validator->errors()->messages()) {
            return ApiService::errorResponse(
                'Проверьте, пожалуйста, корректность введённых данных. Некоторые поля содержат недопустимые значения или пропущены обязательные поля.',
                ApiService::UNPROCESSABLE_CONTENT,
                $validator->errors()->messages(),
                422);
        }

        $credentials = [
            'email' => $validator->validated()['email'],
            'password' => $validator->validated()['password']
        ];

        if (! Auth::attempt($credentials)) {
            return ApiService::errorResponse(
                'Авторизация невозможна, проверьте данные учётной записи',
                ApiService::INVALID_CREDENTIALS,
                [],
                401
            );
        }

        $user = User::where('email', $credentials['email'])->first();
        $token = $request->user()->createToken($user->name);

        $data =  [
            'name' => $user->name,
            'email' => $user->email,
            'token' => $token->plainTextToken
        ];

        return ApiService::successResponse('Успешно', $data);
    }
}
