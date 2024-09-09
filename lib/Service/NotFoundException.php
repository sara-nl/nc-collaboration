<?php

namespace OCA\Collaboration\Service;

class NotFoundException extends ServiceException
{
    private const NOT_FOUND_EXCEPTION = 'NOT_FOUND_EXCEPTION';

    public function __construct(string $message = "")
    {
        parent::__construct($message == "" ? self::NOT_FOUND_EXCEPTION : $message);
    }
}
