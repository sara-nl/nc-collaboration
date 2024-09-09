<?php

namespace OCA\Collaboration\Migration;

use OC\Accounts\AccountManager;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Federation\Invitation;
use OCP\Accounts\IAccountManager;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
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

        print_r("Adding test data\n");

        // update the email of the admin account; email is part of the data json property
        $admin = $this->userManager->get("admin");
        $adminAccount = $this->accountManager->getAccount($admin);
        $adminAccount->setProperty(AccountManager::PROPERTY_EMAIL, "admin@nc-1.nl", "v2-federated", "0");
        $this->accountManager->updateAccount($adminAccount);

        $qb = $this->dbc->getQueryBuilder();

        // add this provider's endpoint
        $qb->insert("appconfig")->values([
            'appid' => $qb->createNamedParameter('collaboration'),
            'configkey' => $qb->createNamedParameter('endpoint'),
            'configvalue' => $qb->createNamedParameter('https://nc-1.nl/apps/collaboration'),
        ]);
        $qb->executeStatement();

        // add invitation provider services
        $qb->insert(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS)->values([
            Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-1.nl'),
            Schema::COLLABORATION_SERVICE_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/collaboration'),
            Schema::COLLABORATION_SERVICE_PROVIDER_NAME => $qb->createNamedParameter('NC 1 University'),
        ]);
        $qb->executeStatement();
        $qb->insert(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS)->values([
            Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-2.nl'),
            Schema::COLLABORATION_SERVICE_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-2.nl/apps/collaboration'),
            Schema::COLLABORATION_SERVICE_PROVIDER_NAME => $qb->createNamedParameter('NC 2 University'),
        ]);
        $qb->executeStatement();

        // create an accepted invitation on nc-1
        $qb->insert(Schema::TABLE_INVITATIONS)->values([
            Schema::INVITATION_USER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TOKEN_ACCEPTED_INVITATION')),
            Schema::INVITATION_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/collaboration'),
            Schema::INVITATION_RECIPIENT_ENDPOINT => $qb->createNamedParameter('https://nc-2.nl/apps/collaboration'),
            Schema::INVITATION_SENDER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_SENDER_EMAIL => $qb->createNamedParameter('jimmie.baker@mail.nc-1-university.nl'),
            Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('Jimmie Baker'),
            Schema::INVITATION_RECIPIENT_CLOUD_ID => $qb->createNamedParameter('lex@nc-2.nl'),
            Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('lex@mail.nc-2-academy.nl'),
            Schema::INVITATION_RECIPIENT_NAME => $qb->createNamedParameter('Lex Lexington'),
            Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
            Schema::INVITATION_STATUS => $qb->createNamedParameter(Invitation::STATUS_ACCEPTED),
        ]);
        $qb->executeStatement();
        // create an open (sent) invitation on nc-1
        $qb->insert(Schema::TABLE_INVITATIONS)->values([
            Schema::INVITATION_USER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TOKEN_OPEN_SENT_INVITATION')),
            Schema::INVITATION_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/collaboration'),
            Schema::INVITATION_SENDER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_SENDER_EMAIL => $qb->createNamedParameter('admin@mail.nc-1-university.nl'),
            Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('A. Dmin'),
            Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('ronnie@nc-2.nl'),
            Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
            Schema::INVITATION_STATUS => $qb->createNamedParameter(Invitation::STATUS_OPEN),
        ]);
        $qb->executeStatement();
        // create an open (received) invitation on nc-1
        $qb->insert(Schema::TABLE_INVITATIONS)->values([
            Schema::INVITATION_USER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TOKEN_OPEN_RECEIVED_INVITATION')),
            Schema::INVITATION_PROVIDER_ENDPOINT => $qb->createNamedParameter('https://nc-2.nl/apps/collaboration'),
            Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('A. Dmin'),
            Schema::INVITATION_RECIPIENT_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
            Schema::INVITATION_RECIPIENT_ENDPOINT => $qb->createNamedParameter('https://nc-1.nl/apps/collaboration'),
            Schema::INVITATION_RECIPIENT_NAME => $qb->createNamedParameter('Jimmie Bo Horn'),
            Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('jimmie@nc-1.nl'),
            Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
            Schema::INVITATION_STATUS => $qb->createNamedParameter(Invitation::STATUS_OPEN),
        ]);
        $qb->executeStatement();
        return $schema;
    }
}
