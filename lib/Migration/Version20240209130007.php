<?php

namespace OCA\Collaboration\Migration;

use OC\Accounts\AccountManager;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Db\Invitation;
use OCP\Accounts\IAccountManager;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version20240209130007 extends SimpleMigrationStep
{
    /** @var IDBConnection */
    private $dbc;
    /** @var LoggerInterface */
    private $logger;
    /** @var IAccountManager */
    private $accountManager;
    /** @var IUserManager */
    private $userManager;

    public function __construct(
        IDBConnection $dbc,
        LoggerInterface $logger,
        IAccountManager $accountManager,
        IUserManager $userManager
    ) {
        $this->dbc = $dbc;
        $this->logger = $logger;
        $this->accountManager = $accountManager;
        $this->userManager = $userManager;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options)
    {
        /**
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();

        if (!$schema->hasTable(Schema::TABLE_INVITATIONS)) {
            //----------------------
            // The invitations table
            //----------------------
            $table = $schema->createTable(Schema::TABLE_INVITATIONS);
            $table->addColumn(Schema::ID, Types::BIGINT, [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn(Schema::INVITATION_USER_ID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_TOKEN, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_PROVIDER_UUID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_PROVIDER_DOMAIN, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_RECIPIENT_PROVIDER_UUID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_RECIPIENT_PROVIDER_DOMAIN, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_SENDER_CLOUD_ID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_SENDER_EMAIL, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_SENDER_NAME, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_RECIPIENT_CLOUD_ID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_RECIPIENT_EMAIL, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_RECIPIENT_NAME, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_TIMESTAMP, Types::INTEGER, [
                'length' => 11,
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn(Schema::INVITATION_STATUS, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->setPrimaryKey([Schema::ID], 'coll_id_primary_index');
            $table->addUniqueIndex([Schema::INVITATION_TOKEN], 'coll_token_index');
        }

        return $schema;
    }
}
