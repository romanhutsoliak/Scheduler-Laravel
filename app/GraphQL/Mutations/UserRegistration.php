<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class UserRegistration
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $name = $args['name'] ?? '';
        if (!$name) $name = preg_replace('#\@.+$#', '', $args['email']);

        $user = User::create([
            'name' => $name,
            'email' => $args['email'],
            'password' => Hash::make($args['password']),
        ]);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $user->createToken('')->plainTextToken,
        ];
    }
}
