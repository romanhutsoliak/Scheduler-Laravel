<?php

namespace App\GraphQL\Mutations;

use App\Models\MissedLanguage;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class MissedLanguageMutator
{
    /**
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @return mixed
     */
    public function create($rootValue, array $args, GraphQLContext $context)
    {
        MissedLanguage::updateOrCreate([
            'text' => $args['text'],
            'language' => $args['language'],
            'url' => $args['url'],
        ], [
            'updated_at' => now(),
        ]);

        return ['result' => 'ok'];
    }
}
