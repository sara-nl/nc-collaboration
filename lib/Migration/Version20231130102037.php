<?php

namespace OCA\Invitation\Migration;

use OCA\Invitation\Db\Schema;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

/**
 * Creates tables: invitation_invitations, invitation_srv_providers
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
        print_r("Changing schema: create tables invitation_invitations, invitation_srv_providers\n");
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
            $table->addColumn(Schema::INVITATION_USER_CLOUD_ID, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_TOKEN, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_PROVIDER_ENDPOINT, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->addColumn(Schema::INVITATION_RECIPIENT_ENDPOINT, Types::STRING, [
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
            $table->setPrimaryKey([Schema::ID]);
            $table->addIndex([Schema::INVITATION_TOKEN], 'invitation_token_index');

            $sql = $this->dbc->getDatabasePlatform()->getCreateTableSQL($table);
            foreach($sql as $statement) {
                $this->logger->debug(print_r($statement, true));
            }
            $this->dbc->executeStatement($sql[0]);
        }

        if (!$schema->hasTable("invitation_invitation_service_providers")) {
            //---------------------------------------
            // the invitation_service_providers table
            //---------------------------------------
            $table = $schema->createTable(Schema::TABLE_INVITATION_SERVICE_PROVIDERS);
            $table->addColumn(Schema::ID, Types::BIGINT, [
                'autoincrement' => true,
                'unsigned' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn(Schema::INVITATION_SERVICE_PROVIDER_DOMAIN, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            // the endpoint of this invitation service provider
            $table->addColumn(Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            // the endpoint of this invitation service provider
            $table->addColumn(Schema::INVITATION_SERVICE_PROVIDER_NAME, Types::STRING, [
                'length' => 255,
                'notnull' => true,
                'default' => '',
            ]);
            $table->setPrimaryKey([Schema::ID], 'invitation_srv_prvdr_primindx');
            $table->addUniqueIndex([Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT], 'endpoint_index');

            $sql = $this->dbc->getDatabasePlatform()->getCreateTableSQL($table);
            foreach($sql as $statement) {
                $this->logger->debug(print_r($statement, true));
            }
            $this->dbc->executeStatement($sql[0]);
        }

        return $schema;
    }
}
