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
        ['root' => '/collaboration',    'name' => 'ocs#find_invitations',           'url' => '/invitations', 'verb' => 'GET'],
        ['root' => '/collaboration',    'name' => 'ocs#find_invitation_for_token',  'url' => '/invitations/{token}', 'verb' => 'GET'],
        ['root' => '/collaboration',    'name' => 'ocs#create_invitation',          'url' => '/invitations', 'verb' => 'POST'],
        ['root' => '/collaboration',    'name' => 'ocs#handle_invite',              'url' => '/handle-invite', 'verb' => 'GET'],

        // ['root' => '/collaboration', 'name' => 'ocs#invitation_get_by_token',       'url' => '/invitation-service/invitations/{token}', 'verb' => 'GET'],
        // ['root' => '/collaboration', 'name' => 'ocs#invitation_update',            'url' => '/invitation-service/invitations/{token}', 'verb' => 'PUT'],

        // ['root' => '/collaboration', 'name' => 'ocs#invitation_find',              'url' => '/invitation-service/invitations', 'verb' => 'GET'],

    ],
];

return array_merge($appRoutes, $ocsRoutes);
