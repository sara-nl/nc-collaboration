<?php

/**
 * Simply delegates all requests to the relevant controller to test the actual implementations.
 */

namespace OCA\Collaboration\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCA\Collaboration\Controller\CollaborationServiceProviderController;
use OCA\Collaboration\Controller\InvitationController;
use OCP\IRequest;

class OcsController extends \OCP\AppFramework\OCSController
{

    public function __construct(
        string $appName,
        IRequest $request,
        private CollaborationServiceProviderController $collaborationServiceProviderController,
        private InvitationController $invitationController,
        private MeshRegistryController $meshRegistryController
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Delegates to InvitationController::createInvitation()
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $email the email address to send the invite to
     * @param string $recipientName the name of the recipient
     * @param string $senderName the name of the sender
     * @param string $message the message for the receiver
     * @return DataResponse the result
     */
    public function createInvitation(string $email = "", string $recipientName = "", string $senderName = "", string $message = ""): DataResponse
    {
        return $this->invitationController->createInvitation($email, $recipientName, $senderName, $message);
    }

    /**
     * Delegates to InvitationController::find()
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $status
     * @return DataResponse the result
     */
    public function findInvitations(string $status = ""): DataResponse
    {
        return $this->invitationController->find($status);
    }

    /**
     * Delegates to InvitationController::getByToken()
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $token
     * @return DataResponse the result
     */
    public function findInvitationForToken(string $token = ""): DataResponse
    {
        return $this->invitationController->getByToken($token);
    }

    // /**
    //  * Delegates to InvitationController
    //  * 
    //  * @NoAdminRequired
    //  * @NoCSRFRequired
    //  * @param string $token
    //  * @return DataResponse
    //  */
    // public function invitationGetByToken(string $token = null): DataResponse
    // {
    //     return $this->invitationController->getByToken($token);
    // }

    // /**
    //  * Delegates to InvitationController
    //  * 
    //  * @NoAdminRequired
    //  * @NoCSRFRequired
    //  * @param string $token
    //  * @return DataResponse
    //  */
    // public function invitationUpdate(string $token = null, string $status): DataResponse
    // {
    //     return $this->invitationController->update($token, $status);
    // }

    // /**
    //  * Delegates to InvitationController
    //  * 
    //  * @NoAdminRequired
    //  * @NoCSRFRequired
    //  * @param string $status
    //  * @return DataResponse
    //  */
    // public function invitationFind(string $status = "", string $remoteUserEmail = ""): DataResponse
    // {
    //     return $this->invitationController->find($status, $remoteUserEmail);
    // }

    // public function invitationGenerateInvite(string $email = "", string $recipientName = "", string $senderName = "", string $message = ""): DataResponse
    // {
    //     return $this->invitationController->generateInvite($email, $recipientName, $senderName, $message);
    // }

    // /**
    //  * Set the name associated with this invitation service provider instance.
    //  *
    //  * @param string $name
    //  * @return DataResponse
    //  * @throws ServiceException
    //  */
    // public function registrySetName(string $name): DataResponse
    // {
    //     return $this->meshRegistryController->setName($name);
    // }
}
