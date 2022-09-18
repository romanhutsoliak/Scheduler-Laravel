<?php

namespace App\GraphQL\Validators\Mutation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateProfileValidator extends Validator
{
    public function rules(): array
    {;
        return [
            'name' => [
                'required'
            ],
            'email' => [
                'required',
                // Rule::unique('users', 'email')->ignore($this->arg('user_id'), 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'The chosen email has been taken',
        ];
    }
}
