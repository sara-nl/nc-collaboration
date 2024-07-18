<?php

// SPDX-FileCopyrightText: Antoon Prins <antoon.prins@surf.nl>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 *  eg. page#index -> OCA\Invitation\Controller\PageController->index()
 *
 *
 * General endpoint syntax follows REST good practices:
 *  /resource/{resourceProperty}?param=value
 *
 * Query parameter names are written camel case:
 *  eg. GET /remote-users?cloudId=jimmie@rd-1.nl@surf.nl
 * Query filter, sorting, pagination, navigation parameter names are written snake case:
 *  eg. _next,
 *      _sort,
 *      timestamp_after (the '_after' filter parameter name is appended to the parameter (resource property) name)
 *
 */

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index',                                'url' => '/', 'verb' => 'GET'],

        ['name' => 'invitation#get_by_token',                   'url' => '/invitations/{token}', 'verb' => 'GET'],
        ['name' => 'invitation#update',                         'url' => '/invitations/{token}', 'verb' => 'PUT'],
        ['name' => 'invitation#find',                           'url' => '/invitations', 'verb' => 'GET'],
        ['name' => 'invitation#generate_invite',                'url' => '/invitations', 'verb' => 'POST'],

        // unprotected
        ['name' => 'mesh_registry#invitation_service_provider', 'url' => '/registry/invitation-service-provider', 'verb' => 'GET'],
        // unprotected
        ['name' => 'mesh_registry#get_name',                    'url' => '/registry/name', 'verb' => 'GET'],

        // OCM - Open Cloud Mesh protocol
        // unprotected
        ['name' => 'ocm#invite_accepted',                       'url' => '/ocm/invite-accepted', 'verb' => 'POST'],
    ]
];
