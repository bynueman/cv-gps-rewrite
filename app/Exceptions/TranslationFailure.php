<?php

namespace App\Exceptions;

use RuntimeException;

class TranslationFailure extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('Translation unavailable: '.$reason);
    }
}
