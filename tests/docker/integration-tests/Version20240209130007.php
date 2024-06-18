<?php

namespace OCA\Invitation\Migration;

use OC\Accounts\AccountManager;
use OCA\Invitation\Db\Schema;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

/**
 * Creates invitation_constants and inserts constants
 */
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
        $prefix = $options['tablePrefix'];

        /**
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();

        print_r("Adding test data\n");

        // update the email of the admin account; email is part of the data json property
        $admin = $this->userManager->get("admin");
        $adminAccount = $this->accountManager->getAccount($admin);
        $adminAccount->setProperty(AccountManager::PROPERTY_EMAIL, "admin@nc-1.nl", "v2-federated", "0");
        $this->accountManager->updateAccount($adminAccount);

        $qb = $this->dbc->getQueryBuilder();

        // add this provider's endpoint
        $qb->insert("appconfig")->values([
            'appid' => $qb->createNamedParameter('invitation'),
            'configkey' => $qb->createNamedParameter('endpoint'),
            'configvalue' => $qb->createNamedParameter('https://nc-1.nl/apps/invitation'),
        ]);
        $qb->executeStatement();

        // add invitation provider services
        $qb->insert(Schema::TABLE_INVITATION_SERVICE_PROVIDERS)->values([
            Schema::INVITATION_SERVICE_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-1.nl'),
            Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/invitation'),
            Schema::INVITATION_SERVICE_PROVIDER_NAME => $qb->createNamedParameter('NC 1 University'),
        ]);
        $qb->executeStatement();
        $qb->insert(Schema::TABLE_INVITATION_SERVICE_PROVIDERS)->values([
            Schema::INVITATION_SERVICE_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-2.nl'),
            Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-2.nl/apps/invitation'),
            Schema::INVITATION_SERVICE_PROVIDER_NAME => $qb->createNamedParameter('NC 2 University'),
        ]);
        $qb->executeStatement();

        // add some invitations
        $qb->insert(Schema::TABLE_INVITATIONS)->values([
            Schema::INVITATION_USER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TEST_UUID_1')),
            Schema::INVITATION_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/invitation'),
            Schema::INVITATION_RECIPIENT_ENDPOINT => $qb->createNamedParameter('https://nc-2.nl/apps/invitation'),
            Schema::INVITATION_SENDER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_SENDER_EMAIL => $qb->createNamedParameter('jimmie.baker@mail.nc-1-university.nl'),
            Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('Jimmie Baker'),
            Schema::INVITATION_RECIPIENT_CLOUD_ID => $qb->createNamedParameter('lex@nc-2.nl'),
            Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('lex@mail.nc-2-academy.nl'),
            Schema::INVITATION_RECIPIENT_NAME => $qb->createNamedParameter('Lex Lexington'),
            Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(1713528254, IQueryBuilder::PARAM_INT),
            Schema::INVITATION_STATUS => $qb->createNamedParameter('withdrawn'),
        ]);
        $qb->executeStatement();
        $qb->insert(Schema::TABLE_INVITATIONS)->values([
            Schema::INVITATION_USER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TEST_UUID_2')),
            Schema::INVITATION_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-2.nl/apps/invitation'),
            Schema::INVITATION_RECIPIENT_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/invitation'),
            Schema::INVITATION_SENDER_CLOUD_ID => $qb->createNamedParameter('admin@nc-2.nl'),
            Schema::INVITATION_SENDER_EMAIL => $qb->createNamedParameter('admin@mail.nc-2-university.nl'),
            Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('A. Dmin'),
            Schema::INVITATION_RECIPIENT_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_RECIPIENT_NAME => $qb->createNamedParameter('Admin'),
            Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(1713528678, IQueryBuilder::PARAM_INT),
            Schema::INVITATION_STATUS => $qb->createNamedParameter('accepted'),
        ]);
        $qb->executeStatement();
        return $schema;
    }
}
