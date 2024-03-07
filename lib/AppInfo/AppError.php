<?php

/**
 * Translatable errors that may be returned or used as exception messages.
 */

namespace OCA\Invitation\AppInfo;

class AppError
{
    /** unspecified error */
    public const ERROR = 'ERROR';

    /** the application has not been configured correctly */
    public const APPLICATION_CONFIGURATION_EXCEPTION = "APPLICATION_CONFIGURATION_EXCEPTION";

    /* route errors */
    /** the request is missing a required parameter */
    public const REQUEST_MISSING_PARAMETER = 'REQUEST_MISSING_PARAMETER';

    /** the invitation cannot be found */
    public const INVITATION_NOT_FOUND = 'INVITATION_NOT_FOUND';

    /* Create invitation errors */
    /** unspecified create invitation error */
    public const CREATE_INVITATION_ERROR = 'CREATE_INVITATION_ERROR';
    /** the recipient's mail is required */
    public const CREATE_INVITATION_NO_RECIPIENT_EMAIL = 'CREATE_INVITATION_NO_RECIPIENT_EMAIL';
    /** the recipient's mail is required */
    public const CREATE_INVITATION_NO_RECIPIENT_NAME = 'CREATE_INVITATION_NO_RECIPIENT_NAME';
    /** the email is invalid */
    public const CREATE_INVITATION_EMAIL_INVALID = 'CREATE_INVITATION_EMAIL_INVALID';
    /** a sender name is required */
    public const CREATE_INVITATION_NO_SENDER_NAME = 'CREATE_INVITATION_NO_SENDER_NAME';
    /** an invitation already exists */
    public const CREATE_INVITATION_EXISTS = 'CREATE_INVITATION_EXISTS';
    /** the sender personal profile has no email specified */
    public const CREATE_INVITATION_ERROR_SENDER_EMAIL_MISSING = 'CREATE_INVITATION_ERROR_SENDER_EMAIL_MISSING';
    /** the sender personal profile has no name specified */
    public const CREATE_INVITATION_ERROR_SENDER_NAME_MISSING = 'CREATE_INVITATION_ERROR_SENDER_NAME_MISSING';
    /** the email used is the email of the sender */
    public const CREATE_INVITATION_EMAIL_IS_OWN_EMAIL = "CREATE_INVITATION_EMAIL_IS_OWN_EMAIL";
    /* Handle recieved invitation errors */
    /** unspecified handle invitation error */
    public const HANDLE_INVITATION_ERROR = 'HANDLE_INVITATION_ERROR';
    /** unable to retrieve the invite information */
    public const GET_INVITE_ERROR = 'GET_INVITE_ERROR';
    /** an invitation already exists */
    public const HANDLE_INVITATION_EXISTS = 'HANDLE_INVITATION_EXISTS';
    /** an invitation already exists */
    public const HANDLE_INVITATION_ALREADY_ACCEPTED = 'HANDLE_INVITATION_ALREADY_ACCEPTED';
    /** invitation is missing the token */
    public const HANDLE_INVITATION_MISSING_TOKEN = 'HANDLE_INVITATION_MISSING_TOKEN';
    /** invitation is missing the provider endpoint */
    public const HANDLE_INVITATION_MISSING_PROVIDER_ENDPOINT = 'HANDLE_INVITATION_MISSING_PROVIDER_ENDPOINT';
    /** invitation is missing the sender name */
    public const HANDLE_INVITATION_MISSING_SENDER_NAME = 'HANDLE_INVITATION_MISSING_SENDER_NAME';
    /** The invitation service provider of the invitation is unknown */
    public const HANDLE_INVITATION_PROVIDER_UNKNOWN = 'HANDLE_INVITATION_PROVIDER_UNKNOWN';
    /** the reponse fields from the OCM /invite-accepted call are invalid */
    public const HANDLE_INVITATION_OCM_INVITE_ACCEPTED_RESPONSE_FIELDS_INVALID = 'HANDLE_INVITATION_INVITE_ACCEPTED_RESPONSE_INVALID';
    /** the invite link is not valid (anymore) */
    public const HANDLE_INVITATION_INVALID_INVITELINK = "HANDLE_INVITATION_INVALID_INVITELINK";

    /* accept invite errors */
    /** error accepting invitation */
    public const ACCEPT_INVITE_ERROR = 'ACCEPT_INVITE_ERROR';
    /** the recipient personal profile has no email specified */
    public const ACCEPT_INVITE_ERROR_RECIPIENT_EMAIL_MISSING = 'ACCEPT_INVITE_ERROR_RECIPIENT_EMAIL_MISSING';
    /** the recipient personal profile has no name specified */
    public const ACCEPT_INVITE_ERROR_RECIPIENT_NAME_MISSING = 'ACCEPT_INVITE_ERROR_RECIPIENT_NAME_MISSING';

    /* decline invite errors */
    /** error declining invite */
    public const DECLINE_INVITE_ERROR = 'DECLINE_INVITE_ERROR';

    /* update invitation errors */
    /** error updating invitation */
    public const UPDATE_INVITATION_ERROR = "UPDATE_INVITATION_ERROR";

    /* Mesh Registry errors */
    /** error retrieving all providers */
    public const MESH_REGISTRY_ALL_PROVIDERS_ERROR = 'MESH_REGISTRY_ALL_PROVIDERS_ERROR';
    /** error adding the invitation service provider */
    public const MESH_REGISTRY_ADD_PROVIDER_ERROR = 'MESH_REGISTRY_ADD_PROVIDER_ERROR';
    /** provider is already registered */
    public const MESH_REGISTRY_ADD_PROVIDER_EXISTS_ERROR = 'MESH_REGISTRY_ADD_PROVIDER_EXISTS_ERROR';
    /** error deleting the invitation service provider */
    public const MESH_REGISTRY_DELETE_PROVIDER_ERROR = 'MESH_REGISTRY_DELETE_PROVIDER_ERROR';
    /** error retrieving the invitation service provider */
    public const MESH_REGISTRY_GET_PROVIDER_ERROR = 'MESH_REGISTRY_GET_PROVIDER_ERROR';
    /** error getting the endpoint */
    public const MESH_REGISTRY_GET_ENDPOINT_ERROR = 'MESH_REGISTRY_GET_ENDPOINT_ERROR';
    /** error setting the name */
    public const MESH_REGISTRY_SET_NAME_ERROR = 'MESH_REGISTRY_SET_NAME_ERROR';
    /** error getting the name */
    public const MESH_REGISTRY_GET_NAME_ERROR = 'MESH_REGISTRY_GET_NAME_ERROR';
    /** error setting the endpoint */
    public const MESH_REGISTRY_SET_ENDPOINT_ERROR = 'MESH_REGISTRY_SET_ENDPOINT_ERROR';
    /** error setting the allow_sharing_with_invited_users_only config param */
    public const MESH_REGISTRY_SET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR = 'MESH_REGISTRY_SET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR';
    /** error getting the allow_sharing_with_invited_users_only config param */
    public const MESH_REGISTRY_GET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR = 'MESH_REGISTRY_GET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR';
    /** error updating the invitation service provider, endpoint is required */
    public const MESH_REGISTRY_UPDATE_PROVIDER_ENDPOINT_NOT_SET_ERROR = 'MESH_REGISTRY_UPDATE_PROVIDER_ENDPOINT_NOT_SET_ERROR';
    /** error updating the this invitation service provider, route not allowed */
    public const MESH_REGISTRY_UPDATE_PROVIDER_ROUTE_NOT_ALLOWED_ERROR = 'MESH_REGISTRY_UPDATE_PROVIDER_ROUTE_NOT_ALLOWED_ERROR';
    /** /registry/invitation-service-provider response is invalid */
    public const MESH_REGISTRY_ENDPOINT_INVITATION_SERVICE_PROVIDER_RESPONSE_INVALID = 'MESH_REGISTRY_ENDPOINT_INVITATION_SERVICE_PROVIDER_RESPONSE_INVALID';

    /* OCM invite errors */
    /** invite accepted error */
    public const OCM_INVITE_ACCEPTED_ERROR = 'OCM_INVITE_ACCEPTED_ERROR';
    /** the invite cannot be found */
    public const OCM_INVITE_ACCEPTED_NOT_FOUND = 'OCM_INVITE_ACCEPTED_NOT_FOUND';
    /** the invite has been accepted already */
    public const OCM_INVITE_ACCEPTED_EXISTS = 'OCM_INVITE_ACCEPTED_EXISTS';

    /** there are no providers found to display on the wayf page */
    public const WAYF_NO_PROVIDERS_FOUND = 'WAYF_NO_PROVIDERS_FOUND';
    /** an unspecified error occurred trying to build the wayf page */
    public const WAYF_ERROR = 'WAYF_ERROR';

    /** the curl POST request responded with an error */
    public const HTTP_POST_CURL_REQUEST_ERROR = 'HTTP_POST_CURL_REQUEST_ERROR';
    /** the curl GET request responded with an error */
    public const HTTP_GET_CURL_REQUEST_ERROR = 'HTTP_GET_CURL_REQUEST_ERROR';

    /* settings admin errors */
    /** only a remote provider can be added in the settings */
    public const SETTINGS_ADD_PROVIDER_IS_NOT_REMOTE_ERROR = 'SETTINGS_ADD_PROVIDER_IS_NOT_REMOTE_ERROR';
}
