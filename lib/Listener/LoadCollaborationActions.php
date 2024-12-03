<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Collaboration\Listener;

use OCA\Collaboration\AppInfo\Application;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadCollaborationActions implements IEventListener
{
    public function handle(Event $event): void
    {
        if (!($event instanceof LoadAdditionalScriptsEvent)) {
            return;
        }

        Util::addInitScript(Application::APP_ID, 'invitation-init');
        Util::addScript(Application::APP_ID, 'invitation-extractAction');
    }
}
