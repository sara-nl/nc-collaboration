<?php

/**
 * Translatable errors that may be returned or used as exception messages.
 */

namespace OCA\Collaboration\AppInfo;

class InvitationError
{
    public const INVITATION_NOT_FOUND = "INVITATION_NOT_FOUND";

    public const CREATE_INVITATION_NO_RECIPIENT_EMAIL = "CREATE_INVITATION_NO_RECIPIENT_EMAIL";
    public const CREATE_INVITATION_NO_RECIPIENT_NAME = "CREATE_INVITATION_NO_RECIPIENT_NAME";
    public const CREATE_INVITATION_NO_SENDER_NAME = "CREATE_INVITATION_NO_SENDER_NAME";
    public const CREATE_INVITATION_EMAIL_INVALID = "CREATE_INVITATION_EMAIL_INVALID";
    public const CREATE_INVITATION_ERROR_SENDER_EMAIL_MISSING = "CREATE_INVITATION_ERROR_SENDER_EMAIL_MISSING";
    public const CREATE_INVITATION_EMAIL_IS_OWN_EMAIL = "CREATE_INVITATION_EMAIL_IS_OWN_EMAIL"; // you cannot send an invite to yourself
    public const CREATE_INVITATION_EXISTS = "CREATE_INVITATION_EXISTS"; // an open or accepted invite already exists
    public const CREATE_INVITATION_ERROR = "CREATE_INVITATION_ERROR";
    public const HANDLE_INVITE_ERROR = 'HANDLE_INVITE_ERROR';
    public const HANDLE_INVITE_MISSING_TOKEN = 'HANDLE_INVITE_MISSING_TOKEN';
    public const ACCEPT_INVITE_MISSING_TOKEN = 'ACCEPT_INVITE_MISSING_TOKEN';
    public const ACCEPT_INVITE_NOT_OPEN = 'ACCEPT_INVITE_NOT_OPEN'; // only an open invite can be accepted
    public const ACCEPT_INVITE_ERROR = 'ACCEPT_INVITE_ERROR';
    public const UPDATE_INVITATION_ERROR = "UPDATE_INVITATION_ERROR";
    public const UPDATE_INVITATION_ERROR_TOKEN_NOT_PROVIDED = "UPDATE_INVITATION_ERROR_TOKEN_NOT_PROVIDED";
    public const UPDATE_INVITATION_ERROR_STATUS_NOT_PROVIDED = "UPDATE_INVITATION_ERROR_STATUS_NOT_PROVIDED";
    public const NEW_INVITATION_ERROR = "NEW_INVITATION_ERROR";
}
