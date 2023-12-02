<?php

namespace App\GraphQL\Mutations;

use App\Models\UserDevice;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UserDeviceMutator
{
    /**
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @return mixed
     */
    public function create($rootValue, array $args, GraphQLContext $context)
    {
        $userDeviceData = ['appVersion' => $args['appVersion']];
        if (! empty($args['notificationToken'])) {
            $userDeviceData['notificationToken'] = $args['notificationToken'];
        }

        return UserDevice::updateOrCreate([
            'userId' => $context->user()->id,
            'deviceId' => $args['deviceId'],
            'platform' => $args['platform'],
            'model' => $args['model'],
            'manufacturer' => $args['manufacturer'],
        ], $userDeviceData);
    }
}
