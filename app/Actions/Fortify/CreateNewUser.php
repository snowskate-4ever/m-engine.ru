<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Auth\RegistrationInviteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $inviteService = app(RegistrationInviteService::class);

        Validator::make($input, [
            'invite' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) use ($inviteService): void {
                    if (! is_string($value) || ! $inviteService->isActiveToken($value)) {
                        $fail(__('ui.auth.register.invalid_invite'));
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input, $inviteService): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $consumed = $inviteService->consumeToken($input['invite'], $user);
            if (! $consumed) {
                throw ValidationException::withMessages([
                    'invite' => __('ui.auth.register.invalid_invite'),
                ]);
            }

            return $user;
        });
    }
}
