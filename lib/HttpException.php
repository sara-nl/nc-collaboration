<?php

namespace OCA\Invitation;

use Exception;

class HttpException extends Exception
{
    private const HTTP_EXCEPTION = 'HTTP_EXCEPTION';

    public function __construct(string $message = "")
    {
        parent::__construct($message == "" ? self::HTTP_EXCEPTION : $message);
    }
}
