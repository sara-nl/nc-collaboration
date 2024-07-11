<?php

namespace OCA\Invitation\Service;

use Exception;

class ApplicationConfigurationException extends Exception
{
    private const APPLICATION_CONFIGURATION_EXCEPTION = 'APPLICATION_CONFIGURATION_EXCEPTION';

    public function __construct(string $message = "")
    {
        parent::__construct($message == "" ? self::APPLICATION_CONFIGURATION_EXCEPTION : $message);
    }
}
