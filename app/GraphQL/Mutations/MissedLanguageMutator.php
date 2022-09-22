<?php

namespace App\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Models\MissedLanguage;

class MissedLanguageMutator {

    /**
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
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
