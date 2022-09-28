<?php

namespace App\GraphQL\Mutations;

use Illuminate\Support\Facades\Auth;

final class Logout
{
    /**
     * @param  null  $_
     * @param  array{}  $args
     */
    public function __invoke($_, array $args)
    {
        $user = Auth::user();
        $user->tokens()->delete();

        if (!empty($args['deviceId'])) {
            $user->devices()->where('deviceId', $args['deviceId'])->delete();
        }

        return [
            'result' => 'ok',
        ];
    }
}
