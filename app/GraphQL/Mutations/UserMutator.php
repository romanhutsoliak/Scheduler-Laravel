<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\CustomException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class UserMutator
{
    /**
     * @param null $rootValue
     * @param mixed[] $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @return mixed
     */
    public function update($rootValue, array $args, GraphQLContext $context)
    {
        // validation: unique email, except current user
        $validationErrors = [];
        if (
            $args['email'] &&
            User::where('email', $args['email'])->where('id', '!=', $context->user()->id)->count()
        ) {
            $validationErrors['email'] = ['The email has already been taken.'];
        }
        if (!$context->user()->email && empty($args['password'])) {
            $validationErrors['password'] = ['Password is required on the first update'];
        }
        if (!empty($validationErrors)) {
            throw new CustomException(
                'Validation failed for the field [updateProfile].',
                ['validation' => $validationErrors]
            );
        }

        $name = $args['name'] ?? '';
        if (!$name && $args['email']) {
            $name = preg_replace('#\@.+$#', '', $args['email']);
        }

        $user = User::find($context->user()->id);
        if ($user) {
            $userData = [
                'name' => $name,
                'email' => $args['email'],
            ];
            if (!empty($args['password'])) {
                $userData = array_merge($userData, [
                    'password' => Hash::make($args['password']),
                ]);
            }
            $user->update($userData);
        }

        return [
            'name' => $user->name,
            'email' => $user->email,
            'timezoneOffset' => $user->timezoneOffset ?? null,
        ];
    }

    /**
     * @param null $rootValue
     * @param mixed[] $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @return mixed
     */
    public function register($rootValue, array $args, GraphQLContext $context)
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
                'name' => $user->name,
                'email' => $user->email,
                'timezoneOffset' => $user->timezoneOffset ?? null,
            ],
            'token' => $user->createToken('')->plainTextToken,
        ];
    }

    /**
     * @param null $rootValue
     * @param mixed[] $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @return mixed
     */
    public function createFromDevice($rootValue, array $args, GraphQLContext $context)
    {
        $initialDeviceId = $args['manufacturer'] . '_' . $args['model'] . '_' . $args['deviceId'];
        $userExists = User::where('initialDeviceId', $initialDeviceId)
            ->first();

        $token = null;
        if (!$userExists) {
            $user = User::create([
                'initialDeviceId' => $initialDeviceId,
                'email' => null,
                'password' => null,
                'timezoneOffset' => $args['timezoneOffset'],
            ]);
            $token = $user->createToken('')->plainTextToken;
        } elseif ($userExists && !$userExists->email) {
            $user = $userExists;
            $token = $userExists->createToken('')->plainTextToken;
        } else {
            $user = User::make();
            $user->email = $userExists->email;
        }

        return [
            'user' => [
                'name' => $user->name ?? '',
                'email' => $user->email ?? '',
                'timezoneOffset' => $user->timezoneOffset ?? null,
            ],
            'token' => $token,
        ];
    }

    /**
     * @param null $rootValue
     * @param mixed[] $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context
     * @return mixed
     */
    public function updateTimezone($rootValue, array $args, GraphQLContext $context)
    {
        $user = User::find($context->user()->id);
        if ($user) {
            $user->update([
                'timezoneOffset' => $args['timezoneOffset']
            ]);
        }

        return $user ?? $context->user();
    }
}
