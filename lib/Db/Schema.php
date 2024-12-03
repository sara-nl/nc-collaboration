<?php

/**
 * Database schema table names.
 */

namespace OCA\Collaboration\Db;

class Schema
{
    public const ID = 'id';

    /* Collaboration Service Providers table */
    public const TABLE_COLLABORATION_SERVICE_PROVIDERS = 'collaboration_srv_providers';
    public const COLLABORATION_SERVICE_PROVIDER_UUID = 'uuid';
    public const COLLABORATION_SERVICE_PROVIDER_NAME = 'name';
    public const COLLABORATION_SERVICE_PROVIDER_DOMAIN = 'domain';
    public const COLLABORATION_SERVICE_PROVIDER_HOST = 'host';

    /* Invitations table */
    public const TABLE_INVITATIONS = 'collaboration_invitations';

    public const INVITATION_USER_ID = 'uid'; // from user account
    public const INVITATION_TOKEN = 'token';
    public const INVITATION_PROVIDER_UUID = 'provider_uuid';
    public const INVITATION_PROVIDER_DOMAIN = 'provider_domain';
    public const INVITATION_RECIPIENT_PROVIDER_UUID = 'recipient_provider_uuid';
    public const INVITATION_RECIPIENT_PROVIDER_DOMAIN = 'recipient_provider_domain';
    public const INVITATION_SENDER_CLOUD_ID = 'sender_cloud_id';
    public const INVITATION_SENDER_EMAIL = 'sender_email';
    public const INVITATION_SENDER_NAME = 'sender_name';
    public const INVITATION_RECIPIENT_CLOUD_ID = 'recipient_cloud_id';
    public const INVITATION_RECIPIENT_EMAIL = 'recipient_email';
    public const INVITATION_RECIPIENT_NAME = 'recipient_name';
    public const INVITATION_TIMESTAMP = 'timestamp';
    public const INVITATION_STATUS = 'status';

    /* Remote Users table */
    // public const TABLE_REMOTE_USERS = 'collaboration_remote_users';
    // public const REMOTEUSER_INVITATION_ID = 'invitation_id';
    // public const REMOTEUSER_CLOUD_ID = 'cloud_id';
    // public const REMOTEUSER_NAME = 'name';
    // public const REMOTEUSER_EMAIL = 'email';
    // public const REMOTEUSER_INSTITUTE = 'institute';

    /* Address Book view */
    // public const VIEW_ADDRESS_BOOK = 'collaboration_view_address_book';
    // public const VADDRESS_BOOK_USER_ID = 'uid'; // from user account
    // public const VADDRESS_BOOK_REMOTE_USER_CLOUD_ID = 'remote_user_cloud_id';
    // public const VADDRESS_BOOK_REMOTE_USER_NAME = 'remote_user_name';
    // public const VADDRESS_BOOK_REMOTE_USER_EMAIL = 'remote_user_email';
    // public const VADDRESS_BOOK_REMOTE_USER_PROVIDER_DOMAIN = 'remote_user_provider_domain';
    // public const VADDRESS_BOOK_REMOTE_USER_PROVIDER_NAME = 'remote_user_provider_name';


    /* Invitations view */
    // public const VIEW_INVITATIONS = 'collaboration_view_invitations';
    // public const VINVITATION_USER_ID = 'uid';
    // public const VINVITATION_TOKEN = 'token';
    // public const VINVITATION_TIMESTAMP = 'timestamp';
    // public const VINVITATION_STATUS = 'status';
    // public const VINVITATION_SEND_RECEIVED = 'sent_received';
    // public const VINVITATION_PROVIDER_ENDPOINT = 'provider_endpoint';
    // public const VINVITATION_RECIPIENT_ENDPOINT = 'recipient_endpoint';
    // public const VINVITATION_SENDER_CLOUD_ID = 'sender_cloud_id';
    // public const VINVITATION_SENDER_EMAIL = 'sender_email';
    // public const VINVITATION_SENDER_NAME = 'sender_name';
    // public const VINVITATION_RECIPIENT_CLOUD_ID = 'recipient_cloud_id';
    // public const VINVITATION_RECIPIENT_EMAIL = 'recipient_email';
    // public const VINVITATION_RECIPIENT_NAME = 'recipient_name';
    // public const VINVITATION_REMOTE_USER_NAME = 'remote_user_name';
    // public const VINVITATION_REMOTE_USER_CLOUD_ID = 'remote_user_cloud_id';
    // public const VINVITATION_REMOTE_USER_EMAIL = 'remote_user_email';
    // public const VINVITATION_REMOTE_USER_PROVIDER_ENDPOINT = 'remote_user_provider_endpoint';
    // public const VINVITATION_REMOTE_USER_PROVIDER_NAME = 'remote_user_provider_name';
}
