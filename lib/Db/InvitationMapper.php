<?php

namespace OCA\Collaboration\Db;

use Exception;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Db\Invitation;
use OCA\Collaboration\Service\NotFoundException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class InvitationMapper extends QBMapper
{
    private LoggerInterface $logger;

    public function __construct(IDBConnection $dbConnection, LoggerInterface $logger)
    {
        parent::__construct($dbConnection, Schema::TABLE_INVITATIONS, Invitation::class);
        $this->logger = $logger;
    }

    /** Returns the invitation with the specified token, or NotFoundException if it could not be found.
     *
     * @param string $token
     * @return Invitation
     * @throws NotFoundException
     */
    public function getByToken(string $token)
    {
        try {
            $qb = $this->db->getQueryBuilder();
            // $qb->automaticTablePrefix(false);
            $qb->select('*')
                ->from(Schema::TABLE_INVITATIONS, 'i')
                ->where($qb->expr()->eq('i.token', $qb->createNamedParameter($token)));
            $this->logger->debug($qb->getSQL(), ['app' => Application::APP_ID]);
            return $this->findEntity($qb);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new NotFoundException($e->getMessage());
        }
        throw new NotFoundException("Invitation not found for token $token");
    }

    /**
     * Returns all invitations matching the specified criteria.
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
     * @return array the invitations
     */
    public function findAll(array $criteria): array
    {
        $qb = $this->db->getQueryBuilder();
        // $qb->automaticTablePrefix(false);
        $query = $qb->select('*')->from(Schema::TABLE_INVITATIONS, 'i')->setMaxResults(100);
        $i = 0;
        foreach ($criteria as $field => $values) {
            if ($i == 0) {
                // $or = $qb->expr()->orX();
                $or = [];
                foreach ($values as $value) {
                    // $or->add($qb->expr()->eq("i.$field", $qb->createNamedParameter($value)));
                    $or[] = $qb->expr()->eq("i.$field", $qb->createNamedParameter($value));
                }
                // $query->where($or);
                $query->where(...$or);
            } else {
                // $or = $qb->expr()->orX();
                $or = [];
                foreach ($values as $value) {
                    // $or->add($qb->expr()->eq("i.$field", $qb->createNamedParameter($value)));
                    $or[] = $qb->expr()->eq("i.$field", $qb->createNamedParameter($value));
                }
                // $query->andWhere($or);
                $query->andWhere(...$or);
            }
            ++$i;
        }
        $this->logger->debug($query->getSQL());
        $query->addOrderBy(Schema::INVITATION_TIMESTAMP, 'DESC');

        return $query->executeQuery()->fetchAll();
    }

    /**
     * Updates the invitation according to the specified fields and values.
     * The token of the invitation must be specified as one of the fields and values.
     *
     * @param array $fieldsAndValues
     * @param string @userCloudID if set only the invitations owned by the user with this cloud ID can be updated
     * @return bool true if an invitation has been updated, false otherwise
     */
    public function updateInvitation(array $fieldsAndValues, string $uid = ''): bool
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $updateQuery = $qb->update(Schema::TABLE_INVITATIONS, 'i');
            if (isset($fieldsAndValues[Schema::INVITATION_TOKEN]) && count($fieldsAndValues) > 1) {
                foreach ($fieldsAndValues as $field => $value) {
                    if ($field != Schema::INVITATION_TOKEN) {
                        $updateQuery->set("i.$field", $qb->createNamedParameter($value));
                    }
                }
                $andWhere = $qb->expr()->andX();
                $andWhere->add($qb->expr()->eq('i.' . Schema::INVITATION_TOKEN, $qb->createNamedParameter($fieldsAndValues[Schema::INVITATION_TOKEN])));
                if ($uid !== '') {
                    $andWhere->add($qb->expr()->eq('i.' . Schema::INVITATION_USER_ID, $qb->createNamedParameter($uid)));
                }
                $updateQuery->where($andWhere);
                $this->logger->debug($updateQuery->getSQL());
                $result = $updateQuery->executeStatement();
                if ($result === 1) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->error('updateInvitation failed with error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
        }
        return false;
    }

    /**
     * Delete all invitations that have one of the specified statuses.
     *
     * @param array $statusses
     * @return void
     * @throws Exception
     */
    public function deleteForStatus(array $statuses): void
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete(Schema::TABLE_INVITATIONS)
                ->where($qb->expr()->in(Schema::INVITATION_STATUS, $qb->createParameter(Schema::INVITATION_STATUS)));
            $qb->setParameter(Schema::INVITATION_STATUS, $statuses, IQueryBuilder::PARAM_STR_ARRAY);
            $qb->executeQuery();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new Exception('An error occurred trying to delete invitations.');
        }
    }
}
