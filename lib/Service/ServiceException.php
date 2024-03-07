<?php

namespace OCA\Invitation\Service;

use Exception;

class ServiceException extends Exception
{
    private const SERVICE_EXCEPTION = 'SERVICE_EXCEPTION';

    public function __construct(string $message = "")
    {
        parent::__construct($message == "" ? self::SERVICE_EXCEPTION : $message);
    }
}
