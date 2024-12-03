<?php

namespace OCA\Collaboration\Migration;

use OCA\Collaboration\Db\Schema;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

/**
 * Creates tables: collaboration_invitations, collaboration_srv_providers
 */
class Version20231130102037 extends SimpleMigrationStep
{
    /** @var IDBConnection */
    private $dbc;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(IDBConnection $dbc, LoggerInterface $logger)
    {
        $this->dbc = $dbc;
        $this->logger = $logger;
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

        if (!$schema->hasTable(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS)) {
            print_r("Changing schema: create table " . Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS);
            //---------------------------------------
            // the collaboration service providers table
            //---------------------------------------
            $table = $schema->createTable(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS);
            $table->addColumn(Schema::ID, Types::BIGINT, [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            // the uuid of this invitation service provider
            $table->addColumn(Schema::COLLABORATION_SERVICE_PROVIDER_UUID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            // the name of this invitation service provider
            $table->addColumn(Schema::COLLABORATION_SERVICE_PROVIDER_NAME, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            // the domain of this collaboration service provider
            $table->addColumn(Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            // the host this collaboration service provider
            $table->addColumn(Schema::COLLABORATION_SERVICE_PROVIDER_HOST, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->setPrimaryKey([Schema::ID], 'coll_srv_prvdr_primindx');
            $table->addUniqueIndex([Schema::COLLABORATION_SERVICE_PROVIDER_UUID], 'uuid_index');
            $table->addUniqueIndex([Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN], 'domain_index');

            // $sql = $this->dbc->getDatabasePlatform()->getCreateTableSQL($table);
            // foreach ($sql as $statement) {
            //     $this->logger->debug(print_r($statement, true));
            // }
            // $this->dbc->executeStatement($sql[0]);
        }

        // if (!$schema->hasTable(Schema::TABLE_INVITATIONS)) {
        //     //----------------------
        //     // The invitations table
        //     //----------------------
        //     $table = $schema->createTable(Schema::TABLE_INVITATIONS);
        //     $table->addColumn(Schema::ID, Types::BIGINT, [
        //         'autoincrement' => true,
        //         'unsigned' => true,
        //         'notnull' => true,
        //         'length' => 20,
        //     ]);
        //     $table->addColumn(Schema::INVITATION_USER_ID, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_TOKEN, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_PROVIDER_DOMAIN, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_RECIPIENT_PROVIDER_DOMAIN, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_SENDER_CLOUD_ID, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_SENDER_EMAIL, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_SENDER_NAME, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_RECIPIENT_CLOUD_ID, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_RECIPIENT_EMAIL, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_RECIPIENT_NAME, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::INVITATION_TIMESTAMP, Types::INTEGER, [
        //         'length' => 11,
        //         'notnull' => true,
        //         'default' => 0,
        //     ]);
        //     $table->addColumn(Schema::INVITATION_STATUS, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->setPrimaryKey([Schema::ID]);
        //     $table->addIndex([Schema::INVITATION_TOKEN], 'invitation_token_index');

        //     $sql = $this->dbc->getDatabasePlatform()->getCreateTableSQL($table);
        //     foreach ($sql as $statement) {
        //         $this->logger->debug(print_r($statement, true));
        //     }
        //     $this->dbc->executeStatement($sql[0]);
        // }

        // if (!$schema->hasTable(Schema::TABLE_REMOTE_USERS)) {
        //     //---------------------------------------
        //     // the remote users table
        //     //---------------------------------------
        //     $table = $schema->createTable(Schema::TABLE_REMOTE_USERS);
        //     $table->addColumn(Schema::ID, Types::BIGINT, [
        //         'autoincrement' => true,
        //         'unsigned' => true,
        //         'notnull' => true,
        //         'length' => 20,
        //     ]);
        //     // the name of this invitation service provider
        //     $table->addColumn(Schema::REMOTEUSER_INVITATION_ID, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => false,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::REMOTEUSER_CLOUD_ID, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::REMOTEUSER_NAME, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::REMOTEUSER_EMAIL, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->addColumn(Schema::REMOTEUSER_INSTITUTE, Types::STRING, [
        //         'length' => 255,
        //         'notnull' => true,
        //         'default' => '',
        //     ]);
        //     $table->setPrimaryKey([Schema::ID]);
        //     $table->addIndex([Schema::REMOTEUSER_CLOUD_ID], 'remote_user_cloud_id_index');

        //     $sql = $this->dbc->getDatabasePlatform()->getCreateTableSQL($table);
        //     foreach ($sql as $statement) {
        //         $this->logger->debug(print_r($statement, true));
        //     }
        //     $this->dbc->executeStatement($sql[0]);
        // }

        return $schema;
    }
}
