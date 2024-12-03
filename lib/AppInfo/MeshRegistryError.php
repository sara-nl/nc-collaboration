<?php

/**
 * Translatable errors that may be returned or used as exception messages.
 */

namespace OCA\Collaboration\AppInfo;

class MeshRegistryError
{
    public const FORWARD_INVITE_MISSING_TOKEN = "FORWARD_INVITE_MISSING_TOKEN";
    public const FORWARD_INVITE_MISSING_PROVIDER = "FORWARD_INVITE_MISSING_PROVIDER";
    public const FORWARD_INVITE_PROVIDER_NOT_FOUND = "FORWARD_INVITE_PROVIDER_NOT_FOUND";
}
