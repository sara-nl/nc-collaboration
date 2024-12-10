<?php

namespace OCA\Collaboration\Migration;

use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Db\CollaborationServiceProvider;
use OCA\Collaboration\Db\Invitation;
use OCA\Collaboration\Service\CollaborationServiceProviderService;
use OCA\Collaboration\Service\InvitationService;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class TestData implements IRepairStep
{

    /** @var IDBConnection */
    private $db;


    /** @var IAppConfig */
    private $config;

    /** @var CollaborationServiceProviderService */
    private CollaborationServiceProviderService $collaborationServiceProviderService;

    /** @var InvitationService */
    private InvitationService $invitationService;

    /**
     * @param IDBConnection $db
     * @param IJobList $jobList
     * @param IAppConfig $config
     */
    public function __construct(
        IDBConnection $db,
        IAppConfig $config,
        CollaborationServiceProviderService $collaborationServiceProviderService,
        InvitationService $invitationService,
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->collaborationServiceProviderService = $collaborationServiceProviderService;
        $this->invitationService = $invitationService;
    }

    /**
     * Returns the step's name
     *
     * @return string
     * @since 9.1.0
     */
    public function getName()
    {
        return 'Test data step';
    }

    /**
     * Add test configuration and test data
     *
     * @param IOutput $output
     * @throws \Exception in case of failure
     * @since 9.1.0
     */
    public function run(IOutput $output)
    {

        print_r("\nSetting provider uuid in configuration");
        $this->config->setValueString(Application::APP_ID, Application::CONFIG_KEY_PROVIDER_UUID, getenv('NC1_PROVIDER_UUID'));

        print_r("\nConfigure providers " . getenv('NC1_DOMAIN') . " and " . getenv('NC1_DOMAIN') . "\n");
        $collaborationServiceProvider = new CollaborationServiceProvider();
        $collaborationServiceProvider->setUuid(getenv('NC1_PROVIDER_UUID'));
        $collaborationServiceProvider->setDomain(getenv('NC1_DOMAIN'));
        $collaborationServiceProvider->setName('NC 1 University');
        $collaborationServiceProvider->setHost('nc-1.nl');

        $this->collaborationServiceProviderService->insert($collaborationServiceProvider);
        $collaborationServiceProvider = new CollaborationServiceProvider();
        $collaborationServiceProvider->setUuid(getenv('NC2_PROVIDER_UUID'));
        $collaborationServiceProvider->setDomain(getenv('NC2_DOMAIN'));
        $collaborationServiceProvider->setName('NC 2 University');
        $collaborationServiceProvider->setHost('nc-2.nl');
        $this->collaborationServiceProviderService->insert($collaborationServiceProvider);

        print_r("Add test invitation with status: " . Invitation::STATUS_INVALID . "\n");
        $invitation = new Invitation();
        $invitation->setUid("admin");
        $invitation->setToken(getenv('TOKEN_INVALID_INVITATION'));
        $invitation->setStatus(Invitation::STATUS_INVALID);
        $invitation->setProviderUuid(getenv('NC1_PROVIDER_UUID'));
        $invitation->setProviderDomain(getenv('NC1_DOMAIN'));
        $invitation->setRecipientEmail("recipient@email.nl");
        $invitation->setTimestamp(time());
        $this->invitationService->insert($invitation);
    }
}
