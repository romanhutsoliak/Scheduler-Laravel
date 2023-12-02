<?php

namespace App\GraphQL\Mutations;

use App\Enums\TaskPeriodTypesEnum;
use App\Models\Task;
use App\Models\TaskCategory;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TaskMutator
{
    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @return mixed
     */
    public function create($rootValue, array $args, GraphQLContext $context)
    {
        if (! empty($args['categoryName'])) {
            $category = TaskCategory::firstOrCreate([
                'name' => $args['categoryName'],
            ]);
            $args['categoryId'] = $category->id;
        }

        /* @var $task Task */
        $task = Task::create($args);
        if ($task->isActive) {
            $task->calculateAndFillNextRunDateTime();
            $task->save();
        }

        // $context->user()->articles()->save($article);

        return $task;
    }

    /**
     * Return a value for the field.
     *
     * @param  null  $rootValue
     * @param  mixed[]  $args
     * @return mixed
     */
    public function update($rootValue, array $args, GraphQLContext $context)
    {
        /* @var $task Task */
        $task = Task::where([
            'id' => $args['id'],
            'userId' => $context->user()->id,
        ])->first();
        if (! $task) {
            return response(['message' => '404'], 422);
        }

        if (! empty($args['categoryName'])) {
            $category = TaskCategory::firstOrCreate([
                'name' => $args['categoryName'],
            ]);
            $args['categoryId'] = $category->id;
        }

        $task->fill($args);
        if ($task->isActive) {
            $task->calculateAndFillNextRunDateTime();
        }
        $task->save();

        return $task;
    }

    public function completeTask($rootValue, array $args, GraphQLContext $context)
    {
        /* @var $task Task */
        $task = Task::where([
            'id' => $args['id'],
            'userId' => $context->user()->id,
        ])->first();
        if (! $task) {
            return response(['message' => '404'], 422);
        }

        $task->history()->create([
            'notes' => $args['notes'] ?? '',
        ]);

        if ($task->periodTypeId === TaskPeriodTypesEnum::Once) {
            $task->isActive = false;
        } elseif ($task->isActive) {
            $task->calculateAndFillNextRunDateTime(true);
        }
        $task->save();

        return $task;
    }

    public function deleteTask($rootValue, array $args, GraphQLContext $context)
    {
        /* @var $task Task */
        $task = Task::where([
            'id' => $args['id'],
            'userId' => $context->user()->id,
        ])->first();
        if (! $task) {
            return response(['message' => '404'], 422);
        }

        $task->delete();

        return ['result' => 'ok'];
    }
}
