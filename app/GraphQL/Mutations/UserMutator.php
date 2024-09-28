<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class UserMutator
{
    /**
     * @param  null  $rootValue
     * @param  mixed[]  $args
     */
    public function update($rootValue, array $args, GraphQLContext $context): array
    {
        $name = $args['name'] ?? '';
        $user = User::query()
            ->find($context->user()->id);

        if ($user) {
            $userData = [
                'name' => $name,
            ];
            $user->update($userData);
        }

        return [
            'name' => $user->name,
            'timezoneOffset' => $user->timezoneOffset ?? null,
        ];
    }

    /**
     * @param  null  $rootValue
     * @param  mixed[]  $args
     */
    public function createFromDevice($rootValue, array $args, GraphQLContext $context): array
    {
        $initialDeviceId = Str::slug($args['manufacturer'].'_'.$args['model'].'_'.$args['deviceId']);

        $userExists = User::query()
            ->where('initialDeviceId', $initialDeviceId)
            ->first();

        $token = null;
        if (! $userExists) {
            $user = new User;
            $user->initialDeviceId = $initialDeviceId;
            $user->timezoneOffset = $args['timezoneOffset'];
            $user->save();

            $token = $user->createToken('')->plainTextToken;
        } elseif (! $userExists->email) {
            $user = $userExists;
            $token = $userExists->createToken('')->plainTextToken;
        } else {
            $user = User::make();
            $user->email = $userExists->email;
        }

        return [
            'user' => [
                'name' => $user->name ?? '',
                'timezoneOffset' => $user->timezoneOffset ?? null,
            ],
            'token' => $token,
        ];
    }

    /**
     * @param  null  $rootValue
     * @param  mixed[]  $args
     */
    public function updateTimezone($rootValue, array $args, GraphQLContext $context): Model|Collection|Builder|Authenticatable|null
    {
        $user = User::query()->find($context->user()->id);
        $user?->update([
            'timezoneOffset' => $args['timezoneOffset'],
        ]);

        return $user ?? $context->user();
    }
}
