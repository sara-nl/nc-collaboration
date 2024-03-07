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
 *      timestamp_after ('_after' filter parameter name appended to entity parameter name)
 * 
 */

declare(strict_types=1);

return [
    'resources' => [
        'note' => ['url' => '/notes'],
        'note_api' => ['url' => '/api/0.1/notes']
    ],
    'routes' => [
        // bespoke API - invitation
        ['name' => 'invitation#index',                      'url' => '/index', 'verb' => 'GET'],
        ['name' => 'invitation#find_by_token',              'url' => '/invitations/{token}', 'verb' => 'GET'],
        ['name' => 'invitation#find',                       'url' => '/invitations', 'verb' => 'GET'],

        // unprotected endpoint /invitation
        ['name' => 'invitation#invitation',                 'url' => '/invite/{token}', 'verb' => 'GET'],
        ['name' => 'invitation#invitation_form',            'url' => '/invitation-form', 'verb' => 'GET'],
        ['name' => 'invitation#generate_invite',            'url' => '/generate-invite', 'verb' => 'POST'],
        ['name' => 'invitation#handle_invite',              'url' => '/handle-invite', 'verb' => 'GET'],
        ['name' => 'invitation#accept_invite',              'url' => '/accept-invite/{token}', 'verb' => 'PUT'],
        ['name' => 'invitation#decline_invite',             'url' => '/decline-invite/{token}', 'verb' => 'PUT'],
        ['name' => 'invitation#update',                     'url' => '/update-invitation', 'verb' => 'PUT'],

        // bespoke API - remote user
        ['name' => 'remote_user#search',                    'url' => '/remote-user/search', 'verb' => 'GET'],
        ['name' => 'remote_user#get_remote_user',           'url' => '/remote-user', 'verb' => 'GET'],

        // bespoke API - mesh registry
        ['name' => 'mesh_registry#forward_invite',          'url' => '/registry/forward-invite', 'verb' => 'GET'],

        // route '/registry/invitation-service-provider' concerns remote invitation service providers
        // returns the properties of the invitation service provider like endpoint, domain, name
        ['name' => 'mesh_registry#invitation_service_provider', 'url' => '/registry/invitation-service-provider', 'verb' => 'GET'],
        // adds a remote invitation service provider
        ['name' => 'mesh_registry#add_invitation_service_provider', 'url' => '/registry/invitation-service-provider', 'verb' => 'POST'],
        // update the properties of this invitation service provider
        ['name' => 'mesh_registry#update_invitation_service_provider', 'url' => '/registry/invitation-service-provider', 'verb' => 'PUT'],
        ['name' => 'mesh_registry#delete_invitation_service_provider', 'url' => '/registry/invitation-service-provider', 'verb' => 'DELETE'],

        // route '/registry/invitation-service-providers' returns all providers
        ['name' => 'mesh_registry#invitation_service_providers', 'url' => '/registry/invitation-service-providers', 'verb' => 'GET'],

        // route '/endpoint' of this instance
        ['name' => 'mesh_registry#get_endpoint', 'url' => '/registry/endpoint', 'verb' => 'GET'],
        ['name' => 'mesh_registry#set_endpoint', 'url' => '/registry/endpoint', 'verb' => 'PUT'],

        // route '/name' of this instance
        ['name' => 'mesh_registry#get_name', 'url' => '/registry/name', 'verb' => 'GET'],
        ['name' => 'mesh_registry#set_name', 'url' => '/registry/name', 'verb' => 'PUT'],

        // route '/share-with-invited-users-only' of this instance
        ['name' => 'mesh_registry#get_allow_sharing_with_invited_users_only', 'url' => '/share-with-invited-users-only', 'verb' => 'GET'],
        ['name' => 'mesh_registry#set_allow_sharing_with_invited_users_only', 'url' => '/share-with-invited-users-only', 'verb' => 'PUT'],

        // OCM - Open Cloud Mesh protocol
        ['name' => 'ocm#invite_accepted',                   'url' => '/ocm/invite-accepted', 'verb' => 'POST'],

        // miscellaneous endpoints
        ['name' => 'page#wayf',                             'url' => '/page/wayf', 'verb' => 'GET'],
        ['name' => 'error#invitation',                      'url' => 'error/invitation', 'verb' => 'GET'],

        [
            'name' => 'note_api#preflighted_cors', 'url' => '/api/0.1/{path}',
            'verb' => 'OPTIONS', 'requirements' => ['path' => '.+']
        ]
    ]
];
