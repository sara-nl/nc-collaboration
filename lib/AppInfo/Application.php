<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: Antoon Prins <antoon.prins@surf.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Collaboration\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Collaboration\Listener\LoadCollaborationActions;
use OCA\Collaboration\Plugin\RemoteUserSearchPlugin;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Collaborators\ISearch;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'collaboration';
    /**
     * The path from which all endpoints start.
     * @var string
     */
    public const APP_PATH = '/apps/' . self::APP_ID;
    public const CONFIG_KEY_PROVIDER_UUID = 'provider_uuid';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

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

        $context->getServerContainer()->get(ISearch::class)->registerPlugin(
            [
                'shareType' => 'SHARE_TYPE_REMOTE',
                'class' => RemoteUserSearchPlugin::class
            ]
        );
    }
}
