<?php

/**
 * Represents an invitation service provider in the mesh. Invitation service providers are registered in the mesh registry.
 *
 */

namespace OCA\Collaboration\Federation;

use JsonSerializable;
use OCA\Collaboration\Db\Schema;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getDomain()
 * @method setDomain(string $domain)
 * @method string getEndpoint()
 * @method setEndpoint(string $endpoint)
 * @method string getName()
 * @method setName(string $name)
 */
class InvitationServiceProvider extends Entity implements JsonSerializable
{
    /** The domain */
    protected $domain;
    /** The endpoint of this invitation service provider */
    protected $endpoint;
    /** The nam associated with this invitation service provider */
    protected $name;

    public function jsonSerialize(): mixed
    {
        return [
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN) => $this->domain,
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_ENDPOINT) => $this->endpoint,
            $this->columnToProperty(Schema::COLLABORATION_SERVICE_PROVIDER_NAME) => $this->name,
        ];
    }
}
