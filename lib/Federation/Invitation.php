<?php

/**
 * This class represents the Invitation entity.
 *
 */

namespace OCA\Invitation\Federation;

use JsonSerializable;
use OCA\Invitation\Db\Schema;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserCloudId()
 * @method void setUserCloudId(string $userCloudId)
 * @method string getToken()
 * @method void setToken(string $token)
 * @method string getProviderEndpoint()
 * @method void setProviderEndpoint(string $providerEndpoint)
 * @method string getRecipientEndpoint()
 * @method void setRecipientEndpoint(string $recipientEndpoint)
 * @method string getSenderCloudId()
 * @method void setSenderCloudId(string $senderCloudId)
 * @method string getSenderEmail()
 * @method void setSenderEmail(string $senderEmail)
 * @method string getSenderName()
 * @method void setSenderName(string $senderName)
 * @method string getRecipientCloudId()
 * @method void setRecipientCloudId(string $recipientCloudId)
 * @method string getRecipientEmail()
 * @method void setRecipientEmail(string $recipientEmail)
 * @method string getRecipientName()
 * @method void setRecipientName(string $recipientName)
 * @method int getTimestamp()
 * @method void setTimestamp(int $timestamp)
 * @method string getStatus()
 * @method void setStatus(string $status)
 */
class Invitation extends Entity implements JsonSerializable
{
    protected $userCloudId;
    protected $token;
    protected $providerEndpoint;
    protected $recipientEndpoint;
    protected $senderCloudId;
    protected $senderEmail;
    protected $senderName;
    protected $recipientCloudId;
    protected $recipientEmail;
    protected $recipientName;
    protected $timestamp;
    protected $status;

    /** the invitation is new */
    public const STATUS_NEW = 'new';
    /** the invitation is open */
    public const STATUS_OPEN = 'open';
    /** the invitation was accepted */
    public const STATUS_ACCEPTED = 'accepted';
    /** the invitation was declined */
    public const STATUS_DECLINED = 'declined';
    /** the open invitation has been revoked */
    public const STATUS_REVOKED = 'revoked';
    /** the accepted invitation has been withdrawn */
    public const STATUS_WITHDRAWN = 'withdrawn';
    /** the invitation is invalid (something has gone wrong) */
    public const STATUS_INVALID = 'invalid';

    public function jsonSerialize()
    {
        return [
            $this->columnToProperty(Schema::INVITATION_USER_CLOUD_ID) => $this->userCloudId,
            $this->columnToProperty(Schema::INVITATION_TOKEN) => $this->token,
            $this->columnToProperty(Schema::INVITATION_PROVIDER_ENDPOINT) => $this->providerEndpoint,
            $this->columnToProperty(Schema::INVITATION_RECIPIENT_ENDPOINT) => $this->recipientEndpoint,
            $this->columnToProperty(Schema::INVITATION_SENDER_CLOUD_ID) => $this->senderCloudId,
            $this->columnToProperty(Schema::INVITATION_SENDER_EMAIL) => $this->senderEmail,
            $this->columnToProperty(Schema::INVITATION_SENDER_NAME) => $this->senderName,
            $this->columnToProperty(Schema::INVITATION_RECIPIENT_CLOUD_ID) => $this->recipientCloudId,
            $this->columnToProperty(Schema::INVITATION_RECIPIENT_EMAIL) => $this->recipientEmail,
            $this->columnToProperty(Schema::INVITATION_RECIPIENT_NAME) => $this->recipientName,
            $this->columnToProperty(Schema::INVITATION_TIMESTAMP) => $this->timestamp,
            $this->columnToProperty(Schema::INVITATION_STATUS) => $this->status,
        ];
    }
}
