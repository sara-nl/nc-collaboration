<?php

namespace OCA\Invitation\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class OcsController extends \OCP\AppFramework\OCSController
{
    /** @var InvitationController */
    private InvitationController $invitationController;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        InvitationController $invitationController,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->invitationController = $invitationController;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function findByToken(string $token = null): DataResponse
    {
        return $this->invitationController->findByToken($token);
    }
}
