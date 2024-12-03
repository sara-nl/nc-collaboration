<?php

namespace OCA\Collaboration\Migration;

use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Federation\Invitation;
use OCP\Accounts\IAccountManager;
use OCP\IAppConfig;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version2Date20240209130007 extends SimpleMigrationStep
{

    /** @var IDBConnection */
    private $dbc;
    /** @var IAppConfig */
    private IAppConfig $config;
    /** @var IAccountManager */
    private $accountManager;
    /** @var IUserManager */
    private $userManager;

    public function __construct(
        IDBConnection $dbc,
        IAppConfig $config,
        IAccountManager $accountManager,
        IUserManager $userManager
    ) {
        $this->dbc = $dbc;
        $this->config = $config;
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

        print_r("\nCreating Collaboration Provider Services for " . getenv('NC2_DOMAIN') . " and " . getenv('NC2_DOMAIN') . " \n");
        $this->config->setValueString(Application::APP_ID, Application::CONFIG_KEY_PROVIDER_UUID, getenv('NC2_PROVIDER_UUID'));

        print_r("Adding test data\n");

        // update the email of the admin account; email is part of the data json property
        $admin = $this->userManager->get("admin");
        $adminAccount = $this->accountManager->getAccount($admin);
        $adminAccount->setProperty(IAccountManager::PROPERTY_EMAIL, "admin@nc-2.nl", "v2-federated", "0");
        $this->accountManager->updateAccount($adminAccount);

        // add collaboration provider services
        $qb = $this->dbc->getQueryBuilder();
        $qb->insert(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS)->values([
            Schema::COLLABORATION_SERVICE_PROVIDER_UUID => $qb->createNamedParameter(getenv('NC1_PROVIDER_UUID')),
            Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-1.nl'),
            Schema::COLLABORATION_SERVICE_PROVIDER_HOST => $qb->createNamedParameter('nc-1.nl'),
            Schema::COLLABORATION_SERVICE_PROVIDER_NAME => $qb->createNamedParameter('NC 1 University'),
        ]);
        $qb->executeStatement();
        $qb->insert(Schema::TABLE_COLLABORATION_SERVICE_PROVIDERS)->values([
            Schema::COLLABORATION_SERVICE_PROVIDER_UUID => $qb->createNamedParameter(getenv('NC2_PROVIDER_UUID')),
            Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-2.nl'),
            Schema::COLLABORATION_SERVICE_PROVIDER_HOST => $qb->createNamedParameter('nc-2.nl'),
            Schema::COLLABORATION_SERVICE_PROVIDER_NAME => $qb->createNamedParameter('NC 2 University'),
        ]);
        $qb->executeStatement();

        // create an accepted (accepted by the receiver on nc-2.nl) invitation on nc-1
        // $qb->insert(Schema::TABLE_INVITATIONS)->values([
        //     Schema::INVITATION_USER_ID => $qb->createNamedParameter('admin'),
        //     Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TOKEN_ACCEPTED_INVITATION')),
        //     Schema::INVITATION_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-1.nl'),
        //     Schema::INVITATION_RECIPIENT_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-2.nl'),
        //     Schema::INVITATION_SENDER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
        //     Schema::INVITATION_SENDER_EMAIL => $qb->createNamedParameter('jimmie.baker@mail.nc-1-university.nl'),
        //     Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('Jimmie Baker'),
        //     Schema::INVITATION_RECIPIENT_CLOUD_ID => $qb->createNamedParameter('lex@nc-2.nl'),
        //     Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('lex@mail.nc-2-academy.nl'),
        //     Schema::INVITATION_RECIPIENT_NAME => $qb->createNamedParameter('Lex Lexington'),
        //     Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
        //     Schema::INVITATION_STATUS => $qb->createNamedParameter(Invitation::STATUS_ACCEPTED),
        // ]);
        // $qb->executeStatement();
        // // create an open (sent to ronnie@nc-2.nl) invitation on nc-1
        // $qb->insert(Schema::TABLE_INVITATIONS)->values([
        //     Schema::INVITATION_USER_ID => $qb->createNamedParameter('admin'),
        //     Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TOKEN_OPEN_SENT_INVITATION')),
        //     Schema::INVITATION_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-1.nl'),
        //     Schema::INVITATION_SENDER_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
        //     Schema::INVITATION_SENDER_EMAIL => $qb->createNamedParameter('admin@mail.nc-1-university.nl'),
        //     Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('A. Dmin'),
        //     Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('ronnie@nc-2.nl'),
        //     Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
        //     Schema::INVITATION_STATUS => $qb->createNamedParameter(Invitation::STATUS_OPEN),
        // ]);
        // $qb->executeStatement();
        // // create an open (received from nc-2.nl) invitation on nc-1
        // $qb->insert(Schema::TABLE_INVITATIONS)->values([
        //     Schema::INVITATION_USER_ID => $qb->createNamedParameter('admin'),
        //     Schema::INVITATION_TOKEN => $qb->createNamedParameter(getenv('TOKEN_OPEN_RECEIVED_INVITATION')),
        //     Schema::INVITATION_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-2.nl'),
        //     Schema::INVITATION_SENDER_NAME => $qb->createNamedParameter('A. Dmin'),
        //     Schema::INVITATION_RECIPIENT_CLOUD_ID => $qb->createNamedParameter('admin@nc-1.nl'),
        //     Schema::INVITATION_RECIPIENT_PROVIDER_DOMAIN => $qb->createNamedParameter('nc-1.nl'),
        //     Schema::INVITATION_RECIPIENT_NAME => $qb->createNamedParameter('Jimmie Bo Horne'),
        //     Schema::INVITATION_RECIPIENT_EMAIL => $qb->createNamedParameter('jimmie@nc-1.nl'),
        //     Schema::INVITATION_TIMESTAMP => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
        //     Schema::INVITATION_STATUS => $qb->createNamedParameter(Invitation::STATUS_OPEN),
        // ]);
        // $qb->executeStatement();

        return $schema;
    }
}
