<?php
/**
 * Simply delegates all requests to the relevant controller to test the actual implementations.
 */
namespace OCA\Collaboration\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class OcsController extends \OCP\AppFramework\OCSController
{
    /** @var InvitationController */
    private InvitationController $invitationController;
    /** @var MeshRegistryController */
    private MeshRegistryController $meshRegistryController;

    public function __construct(
        string $appName,
        IRequest $request,
        InvitationController $invitationController,
        MeshRegistryController $meshRegistryController
    ) {
        parent::__construct($appName, $request);
        $this->invitationController = $invitationController;
        $this->meshRegistryController = $meshRegistryController;
    }

    /**
     * Delegates to InvitationController
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $token
     * @return DataResponse
     */
    public function invitationGetByToken(string $token = null): DataResponse
    {
        return $this->invitationController->getByToken($token);
    }

    /**
     * Delegates to InvitationController
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $token
     * @return DataResponse
     */
    public function invitationUpdate(string $token = null, string $status): DataResponse
    {
        return $this->invitationController->update($token, $status);
    }

    /**
     * Delegates to InvitationController
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $status
     * @return DataResponse
     */
    public function invitationFind(string $status = "", string $remoteUserEmail = ""): DataResponse
    {
        return $this->invitationController->find($status, $remoteUserEmail);
    }

    public function invitationGenerateInvite(string $email = "", string $recipientName = "", string $senderName = "", string $message = ""): DataResponse
    {
        return $this->invitationController->generateInvite($email, $recipientName, $senderName, $message);
    }

    /**
     * Set the name associated with this invitation service provider instance.
     *
     * @param string $name
     * @return DataResponse
     * @throws ServiceException
     */
    public function registrySetName(string $name): DataResponse
    {
        return $this->meshRegistryController->setName($name);
    }
}
