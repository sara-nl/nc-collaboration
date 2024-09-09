<?php

// SPDX-FileCopyrightText: Antoon Prins <antoon.prins@surf.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Adds the routes required for testing to the app routes.
 * 
 */

declare(strict_types=1);

$appRoutes = include 'app-routes.php';

$ocsRoutes = [
    'ocs' => [
        ['root' => '/collaboration', 'name' => 'ocs#invitation_get_by_token',      'url' => '/invitations/{token}', 'verb' => 'GET'],
        ['root' => '/collaboration', 'name' => 'ocs#invitation_update',            'url' => '/invitations/{token}', 'verb' => 'PUT'],
        ['root' => '/collaboration', 'name' => 'ocs#invitation_find',              'url' => '/invitations', 'verb' => 'GET'],
        ['root' => '/collaboration', 'name' => 'ocs#invitation_generate_invite',   'url' => '/invitations', 'verb' => 'POST'],

    ],
];

return array_merge($appRoutes, $ocsRoutes);
