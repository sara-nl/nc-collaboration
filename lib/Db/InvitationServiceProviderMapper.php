<?php

/**
 * The invitation service provider mapper.
 */

namespace OCA\Collaboration\Db;

use Exception;
use OCA\Collaboration\AppInfo\InvitationApp;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Service\NotFoundException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class InvitationServiceProviderMapper extends QBMapper
{
    private LoggerInterface $logger;

    public function __construct(IDBConnection $dbConnection, LoggerInterface $logger)
    {
        parent::__construct($dbConnection, Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, InvitationServiceProvider::class);
        $this->logger = $logger;
    }

    /**
     * Returns the local (this instance's) invitation service provider.
     *
     * @param string $endpoint
     * @return InvitationServiceProvider
     * @throws NotFoundException
     */
    public function getInvitationServiceProvider(string $endpoint): InvitationServiceProvider
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $result = $qb->select('*')
                ->from(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, 'dp')
                ->where($qb->expr()->eq('dp.' . Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT, $qb->createNamedParameter($endpoint)))
                ->executeQuery()->fetch();
            if (is_array($result)) {
                return $this->createInvitationServiceProvider($result);
            }
            throw new NotFoundException("Error retrieving the invitation service provider with endpoint '$endpoint'");
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
            throw new NotFoundException("Error retrieving the endpoint provider with endpoint '$endpoint'");
        }
    }

    // /**
    //  * Returns all invitation service providers
    //  *
    //  * @return array
    //  * @throws NotFoundException
    //  */
    // public function allInvitationServiceProviders(): array
    // {
    //     try {
    //         $qb = $this->db->getQueryBuilder();
    //         $qb->select('*')
    //             ->from(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, 'dp');
    //         return $this->createInvitationServiceProviders($qb->executeQuery()->fetchAll());
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
    //         throw new NotFoundException('Error retrieving all invitation service providers');
    //     }
    // }

    /**
     * Builds and returns a new InvitationServiceProvider object from the specified associative array.
     * @param array $associativeArray
     * @return InvitationServiceProvider
     */
    private function createInvitationServiceProvider(array $associativeArray): InvitationServiceProvider
    {
        if (isset($associativeArray) && count($associativeArray) > 0) {
            $invitationServiceProvider = new InvitationServiceProvider();
            $invitationServiceProvider->setId($associativeArray['id']);
            $invitationServiceProvider->setDomain($associativeArray[Schema::INVITATION_SERVICE_PROVIDER_DOMAIN]);
            $invitationServiceProvider->setEndpoint($associativeArray[Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT]);
            $invitationServiceProvider->setName($associativeArray[Schema::INVITATION_SERVICE_PROVIDER_NAME]);
            return $invitationServiceProvider;
        }
        $this->logger->error('Unable to create a new InvitationServiceProvider from associative array: ' . print_r($associativeArray, true), ['app' => InvitationApp::APP_NAME]);
        return null;
    }

    /**
     * Builds and returns an array of new InvitationServiceProvider objects from the specified associatives array.
     * @param array $associativeArrays
     * @return array
     */
    private function createInvitationServiceProviders(array $associativeArrays): array
    {
        $invitationServiceProviders = [];
        if (isset($associativeArrays) && count($associativeArrays) > 0) {
            foreach ($associativeArrays as $associativeArray) {
                array_push($invitationServiceProviders, $this->createInvitationServiceProvider($associativeArray));
            }
        }
        return $invitationServiceProviders;
    }
}
