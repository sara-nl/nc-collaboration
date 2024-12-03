<?php

/**
 * Represents the collaboration service provider that this application is.
 *
 */

namespace OCA\Collaboration\Db;

use JsonSerializable;
use OCA\Collaboration\Db\Schema;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUuid()
 * @method void setUuid(string $uuid)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getDomain()
 * @method void setDomain(string $domain)
 * @method string getHost()
 * @method void setHost(string $host)
 */
class CollaborationServiceProvider extends Entity implements JsonSerializable
{
    protected string $uuid = "";
    protected string $name = "";
    protected string $domain = "";
    protected string $host = "";


    public function jsonSerialize(): mixed
    {
        return [
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_UUID) => $this->uuid,
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN) => $this->domain,
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_NAME) => $this->name,
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_HOST) => $this->host,
        ];
    }
}
