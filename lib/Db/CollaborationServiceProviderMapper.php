<?php

namespace OCA\Collaboration\Db;

use Exception;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Service\NotFoundException;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class CollaborationServiceProviderMapper extends QBMapper
{
    private LoggerInterface $logger;

    public function __construct(IDBConnection $dbConnection, LoggerInterface $logger)
    {
        parent::__construct($dbConnection, Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, CollaborationServiceProvider::class);
        $this->logger = $logger;
    }

    /**
     * Returns an arry of all collaboration service providers
     * @return array[CollaborationServiceProvider] all collaboration service providers
     */
    public function allProviders(): array
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, 'i')->setMaxResults(100);
            return $this->findEntities($qb);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new NotFoundException($e->getMessage());
        }
        throw new NotFoundException("No Collaboration Service Providers found.");
    }

    /** Returns the collaboration service provider with the specified uuid, or NotFoundException if it could not be found.
     *
     * @param string $uuid
     * @return CollaborationServiceProvider
     * @throws NotFoundException
     */
    public function getByUuid(string $uuid): CollaborationServiceProvider
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, 'i')
                ->where($qb->expr()->eq('i.uuid', $qb->createNamedParameter($uuid)));
            return $this->findEntity($qb);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new NotFoundException($e->getMessage());
        }
        throw new NotFoundException("Collaboration Service Provider not found for uuid $uuid");
    }

    /**
     * Returns all providers matching the specified criteria.
     * Expected $criteria format:
     * [
     *   column_1 => [value1, value2],
     *   column_2 => [value3],
     *   ... etc.
     * ]
     * Will yield the following SQL:
     *  SELECT * WHERE (column_1 = value1 OR column_1 = value2) AND (column_2 = value3) AND (...etc.)
     *
     * @param array $criteria
     * @return array the CollaborationServiceProvider entities
     */
    public function findAll(array $criteria): array
    {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')->from(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS, 'i')->setMaxResults(100);
        $i = 0;
        foreach ($criteria as $field => $values) {
            if ($i == 0) {
                $or = [];
                foreach ($values as $value) {
                    $or[] = $qb->expr()->eq("i.$field", $qb->createNamedParameter($value));
                }
                $query->where(...$or);
            } else {
                $or = [];
                foreach ($values as $value) {
                    $or[] = $qb->expr()->eq("i.$field", $qb->createNamedParameter($value));
                }
                $query->andWhere(...$or);
            }
            ++$i;
        }
        $this->logger->debug($query->getSQL());
        $query->addOrderBy(Schema::COLLABORATION_SERVICE_PROVIDER_NAME, 'DESC');

        $result = $this->findEntities($query);
        return $result;
    }

    // /**
    //  * Builds and returns a new CollaborationServiceProvider object from the specified associative array.
    //  *
    //  * @param array $associativeArray
    //  * @return CollaborationServiceProvider
    //  */
    // private function getCollaborationServiceProvider(array $associativeArray): CollaborationServiceProvider
    // {
    //     if (isset($associativeArray) && count($associativeArray) > 0) {
    //         $collaborationServiceProvider = new CollaborationServiceProvider();
    //         $collaborationServiceProvider->setId($associativeArray['id']);
    //         $collaborationServiceProvider->setUuid($associativeArray[Schema::COLLABORATION_SERVICE_PROVIDER_UUID]);
    //         $collaborationServiceProvider->setDomain($associativeArray[Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN]);
    //         $collaborationServiceProvider->setName($associativeArray[Schema::COLLABORATION_SERVICE_PROVIDER_NAME]);
    //         $collaborationServiceProvider->setHost($associativeArray[Schema::COLLABORATION_SERVICE_PROVIDER_HOST]);
    //         return $collaborationServiceProvider;
    //     }
    //     $this->logger->error('Unable to create a new CollaborationServiceProvider object from associative array: ' . print_r($associativeArray, true), ['app' => Application::APP_ID]);
    //     return null;
    // }
}
