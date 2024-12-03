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
        /** Collaboration Service Provider */
        ['name' => 'collaboration_service_provider#provider',   'url' => '/provider', 'verb' => 'GET'],
        ['name' => 'collaboration_service_provider#services',   'url' => '/provider/services', 'verb' => 'GET'],

        /** Mesh Registry Service */
        ['name' => 'mesh_registry#providers',                   'url' => '/mesh-registry/providers', 'verb' => 'GET'],
        ['name' => 'mesh_registry#forwardInvite',               'url' => '/mesh-registry/forward-invite', 'verb' => 'GET'],
        ['name' => 'mesh_registry#wayfError',                   'url' => '/mesh-registry/forward-invite/error', 'verb' => 'GET'],

        /** Invitation Service */
        ['name' => 'invitation#find',                           'url' => '/invitations', 'verb' => 'GET'],
        ['name' => 'invitation#get_by_token',                   'url' => '/invitations/{token}', 'verb' => 'GET'],
        ['name' => 'invitation#create_invitation',              'url' => '/invitations', 'verb' => 'POST'],
        ['name' => 'invitation#handle_invite',                  'url' => '/handle-invite', 'verb' => 'GET'],

        // ['name' => 'page#index',                                'url' => '/', 'verb' => 'GET'],

        // ['name' => 'invitation#find',                           'url' => '/invitation-service/invitations', 'verb' => 'GET'],
        // ['name' => 'invitation#generate_invite',                'url' => '/invitation-service/invitations', 'verb' => 'POST'],
        // ['name' => 'invitation#get_by_token',                   'url' => '/invitation-service/invitations/{token}', 'verb' => 'GET'],
        // ['name' => 'invitation#update',                         'url' => '/invitation-service/invitations/{token}', 'verb' => 'PATCH'],

        // /**
        //  * Mesh Registry Service
        //  */
        // ['name' => 'mesh_registry#providers',                   'url' => '/mesh-registry/providers/{uuid}', 'verb' => 'GET'],

        // // OCM - Open Cloud Mesh protocol
        // // unprotected
        // ['name' => 'ocm#invite_accepted',                       'url' => '/ocm/invite-accepted', 'verb' => 'POST'],
    ]
];
