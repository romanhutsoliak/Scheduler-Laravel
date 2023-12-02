<?php

namespace App\Exceptions;

use Exception;
use Nuwave\Lighthouse\Exceptions\RendersErrorsExtensions;

class CustomException extends Exception implements RendersErrorsExtensions
{
    /**
     * @var @string
     */
    protected $category;

    protected $data;

    public function __construct(string $message, array $data = [], string $category = 'validation')
    {
        parent::__construct($message);

        $this->data = $data;
        $this->category = $category;
    }

    /**
     * Returns true when exception message is safe to be displayed to a client.
     */
    public function isClientSafe(): bool
    {
        return true;
    }

    /**
     * Returns string describing a category of the error.
     *
     * Value "graphql" is reserved for errors produced by query parsing or validation, do not use it.
     *
     * @api
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * Return the content that is put in the "extensions" part
     * of the returned error.
     */
    public function extensionsContent(): array
    {
        return $this->data;
    }
}
