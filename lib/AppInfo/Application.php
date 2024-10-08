<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Antoon Prins <antoon.prins@surf.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Collaboration\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Collaboration\Listener\LoadCollaborationActions;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'collaboration';

    public const CONFIG_ALLOW_SHARING_WITH_INVITED_USERS_ONLY = 'allow_sharing_with_invited_users_only';

    public const INVITATION_EMAIL_SUBJECT = 'INVITATION_EMAIL_SUBJECT';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }


    public function register(IRegistrationContext $context): void
    {
        /*
        * For further information about the app bootstrapping, please refer to our documentation:
        * https://docs.nextcloud.com/server/latest/developer_manual/app_development/bootstrap.html
        */
        // Register your services, event listeners, etc.
		$context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadCollaborationActions::class);
		// $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadCollaborationActions::class);
    }

    public function boot(IBootContext $context): void
    {
        /*
        * For further information about the app bootstrapping, please refer to our documentation:
        * https://docs.nextcloud.com/server/latest/developer_manual/app_development/bootstrap.html
        */
        // Prepare your app.
    }
}
