<?php

/**
 * OCM controller
 */

namespace OCA\Invitation\Controller;

use OCA\Invitation\AppInfo\InvitationApp;
use OCA\Invitation\AppInfo\AppError;
use OCA\Invitation\AppInfo\Application;
use OCA\Invitation\Db\Schema;
use OCA\Invitation\Federation\Invitation;
use OCA\Invitation\Service\InvitationService;
use OCA\Invitation\Service\NotFoundException;
use OCA\Invitation\Service\ServiceException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Class OcmController.
 * Enhances the existing federatedfilesharing app with the ocm endpoint '/invite-accepted'
 *
 */
class OcmController extends Controller
{
    private InvitationService $invitationService;
    private LoggerInterface $logger;

    public function __construct(IRequest $request, InvitationService $invitationService, LoggerInterface $logger)
    {
        parent::__construct(Application::APP_ID, $request);
        $this->invitationService = $invitationService;
        $this->logger = $logger;
    }

    /**
     * Inform the sender of the invite that it has been accepted by the recipient.
     *
     * A previously established invitation relationship between sender and receiver will be replaced with this new one,
     * provided there is an actual open invite for this /invite-accepted request.
     *
     * @NoCSRFRequired
     * @PublicPage
     * @param string $recipientProvider maps to recipient_endpoint in the Invitation entity
     * @param string $token the invite token
     * @param string $userID the recipient cloud ID
     * @param string $email the recipient email
     * @param string $name the recipient name
     * @return DataResponse
     */
    public function inviteAccepted(
        string $recipientProvider = '',
        string $token = '',
        string $userID = '',
        string $email = '',
        string $name = ''
    ): DataResponse {
        $this->logger->debug(" - request: " . print_r(\OC::$server->getRequest(), true));
        if (trim($recipientProvider) == '') {
            return new DataResponse(
                [
                    'message' => 'recipient provider missing'
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if ($token == '') {
            return new DataResponse(
                [
                    'message' => 'sender token missing'
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if ($userID == '') {
            return new DataResponse(
                [
                    'message' => 'recipient user ID missing'
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if ($email == '') {
            return new DataResponse(
                [
                    'message' => 'recipient email missing'
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if ($name == '') {
            return new DataResponse(
                [
                    'message' => 'recipient name missing'
                ],
                Http::STATUS_NOT_FOUND
            );
        }

        $invitation = null;
        try {
            $invitation = $this->invitationService->getByToken($token, false);
        } catch (NotFoundException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::OCM_INVITE_ACCEPTED_NOT_FOUND
                ],
                Http::STATUS_NOT_FOUND
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::OCM_INVITE_ACCEPTED_ERROR
                ],
                Http::STATUS_NOT_FOUND
            );
        }

        // check if there are not an established invitation relations already
        // and remove those
        $existingInvitationsSent = $this->invitationService->findAll([
            Schema::VINVITATION_SENDER_CLOUD_ID => [$invitation->getSenderCloudId()],
            Schema::VINVITATION_RECIPIENT_CLOUD_ID => [$userID],
            Schema::VINVITATION_STATUS => [Invitation::STATUS_ACCEPTED],
        ], false);
        $existingInvitationsReceived = $this->invitationService->findAll([
            Schema::VINVITATION_RECIPIENT_CLOUD_ID => [$invitation->getSenderCloudId()],
            Schema::VINVITATION_SENDER_CLOUD_ID => [$userID],
            Schema::VINVITATION_STATUS => [Invitation::STATUS_ACCEPTED],
        ], false);
        $existingInvitations = array_merge($existingInvitationsSent, $existingInvitationsReceived);
        if (count($existingInvitations) > 0) {
            foreach ($existingInvitations as $existingInvitation) {
                $this->logger->debug("A previous established invitation relation exists. Withdrawing that one.", ['app' => Application::APP_ID]);
                $updateResult = $this->invitationService->update([
                    Schema::INVITATION_TOKEN => $existingInvitation->getToken(),
                    Schema::INVITATION_STATUS => Invitation::STATUS_WITHDRAWN,
                ], false);
                if ($updateResult == false) {
                    return new DataResponse(
                        [
                            'message' => AppError::OCM_INVITE_ACCEPTED_ERROR,
                        ],
                        Http::STATUS_NOT_FOUND,
                    );
                }
            }
        }

        // update the invitation with the receiver's info
        $updateResult = $this->invitationService->update([
            Schema::VINVITATION_TOKEN => $invitation->getToken(),
            Schema::VINVITATION_RECIPIENT_ENDPOINT => $recipientProvider,
            Schema::VINVITATION_RECIPIENT_CLOUD_ID => $userID,
            Schema::VINVITATION_RECIPIENT_EMAIL => $email,
            Schema::VINVITATION_RECIPIENT_NAME => $name,
            Schema::VINVITATION_STATUS => Invitation::STATUS_ACCEPTED,
        ], false);
        if ($updateResult == false) {
            $this->logger->error("Update failed for invitation with token '$token'", ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::OCM_INVITE_ACCEPTED_ERROR
                ],
                Http::STATUS_NOT_FOUND
            );
        }

        return new DataResponse(
            [
                'userID' => $invitation->getSenderCloudId(),
                'email' => $invitation->getSenderEmail(),
                'name' => $invitation->getSenderName(),
            ],
            Http::STATUS_OK
        );
    }
}
