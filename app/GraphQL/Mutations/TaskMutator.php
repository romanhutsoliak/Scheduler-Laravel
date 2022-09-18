<?php

namespace App\GraphQL\Mutations;

use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use App\Models\Task;

class TaskMutator
{
    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return mixed
     */
    public function create($rootValue, array $args, GraphQLContext $context)
    {
        $task = Task::create($args);
        $task->calculateNextRunDateTime(true);
        $task->save();

        // $context->user()->articles()->save($article);

        return $task;
    }

    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return mixed
     */
    public function update($rootValue, array $args, GraphQLContext $context)
    {
        $task = Task::find($args['id']);
        if (!$task) return response(['message' => '404'], 422);

        $task->fill($args);
        $task->calculateNextRunDateTime(true);
        $task->save();

        return $task;
    }

    public function completeTask($rootValue, array $args, GraphQLContext $context)
    {
        $task = Task::find($args['id']);
        if (!$task) return response(['message' => '404'], 422);

        $task->history()->create([
            'notes' => $args['notes'] ?? ''
        ]);

        $task->calculateNextRunDateTime(true, true);
        $task->save();

        return $task;
    }
}
