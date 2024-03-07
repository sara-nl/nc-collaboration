<?php

/**
 * This class represents the Invitation view entity.
 *
 */

namespace OCA\Invitation\Federation;

use JsonSerializable;
use OCA\Invitation\Db\Schema;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getToken()
 * @method void setToken(string $token)
 * @method string getSentReceived()
 * @method void setSentReceived(string sentReceived)
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
 * @method string getUserCloudID()
 * @method void setUserCloudID(string userCloudID)
 * @method string getRemoteUserName()
 * @method void setRemoteUserName(string remoteUserName)
 * @method string getRemoteCloudID()
 * @method void setRemoteUserCloudID(string remoteUserCloudID)
 * @method string getRemoteUserEmail()
 * @method void setRemoteUserEmail(string remoteUserEmail)
 * @method int getTimestamp()
 * @method void setTimestamp(int $timestamp)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method string getRemoteUserEmail()
 * @method void setRemoteUserEmail(string $remoteUserEmail)
 * @method string getRemoteUserProviderEndpoint()
 * @method void setRemoteUserProviderEndpoint(string $remoteUserProviderEndpoint)
 * @method string getRemoteUserProviderName()
 * @method void setRemoteUserProviderName(string $remoteUserProviderName)
 */
class VInvitation extends Entity implements JsonSerializable
{
    protected $token;
    protected $timestamp;
    protected $status;
    protected $userCloudID;
    protected $sentReceived;
    protected $providerEndpoint;
    protected $recipientEndpoint;
    protected $senderCloudId;
    protected $senderEmail;
    protected $senderName;
    protected $recipientCloudId;
    protected $recipientEmail;
    protected $recipientName;
    protected $remoteUserCloudID;
    protected $remoteUserName;
    /** the remote user email */
    protected $remoteUserEmail;
    /** the remote user provider endpoint */
    protected $remoteUserProviderEndpoint;
    /** the remote user provider name */
    protected $remoteUserProviderName;

    public function jsonSerialize(): mixed
    {
        return [
            $this->columnToProperty(Schema::VINVITATION_TOKEN) => $this->token,
            $this->columnToProperty(Schema::VINVITATION_TIMESTAMP) => $this->timestamp,
            $this->columnToProperty(Schema::VINVITATION_STATUS) => $this->status,
            $this->columnToProperty(Schema::VINVITATION_USER_CLOUD_ID) => $this->userCloudID,
            $this->columnToProperty(Schema::VINVITATION_SEND_RECEIVED) => $this->sentReceived,
            $this->columnToProperty(Schema::VINVITATION_PROVIDER_ENDPOINT) => $this->providerEndpoint,
            $this->columnToProperty(Schema::VINVITATION_RECIPIENT_ENDPOINT) => $this->recipientEndpoint,
            $this->columnToProperty(Schema::VINVITATION_SENDER_CLOUD_ID) => $this->senderCloudId,
            $this->columnToProperty(Schema::VINVITATION_SENDER_NAME) => $this->senderName,
            $this->columnToProperty(Schema::VINVITATION_SENDER_EMAIL) => $this->senderEmail,
            $this->columnToProperty(Schema::VINVITATION_RECIPIENT_CLOUD_ID) => $this->recipientCloudId,
            $this->columnToProperty(Schema::VINVITATION_RECIPIENT_EMAIL) => $this->recipientEmail,
            $this->columnToProperty(Schema::VINVITATION_RECIPIENT_NAME) => $this->recipientName,
            $this->columnToProperty(Schema::VINVITATION_REMOTE_USER_NAME) => $this->remoteUserName,
            $this->columnToProperty(Schema::VINVITATION_REMOTE_USER_CLOUD_ID) => $this->remoteUserCloudID,
            $this->columnToProperty(Schema::VINVITATION_REMOTE_USER_EMAIL) => $this->remoteUserEmail,
            $this->columnToProperty(Schema::VINVITATION_REMOTE_USER_PROVIDER_ENDPOINT) => $this->remoteUserProviderEndpoint,
            $this->columnToProperty(Schema::VINVITATION_REMOTE_USER_PROVIDER_NAME) => $this->remoteUserProviderName,
        ];
    }
}
