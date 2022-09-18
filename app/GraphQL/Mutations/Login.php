<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class Login
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $user = User::where('email', $args['email'])
            ->whereNotNull('email')->whereNotNull('password')->first();

        if (
            empty($args['email']) || empty($args['password']) ||
            !$user || !Hash::check($args['password'], $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'timezoneOffset' => $user->timezoneOffset,
            ],
            'token' => $user->createToken('')->plainTextToken,
        ];
    }
}
