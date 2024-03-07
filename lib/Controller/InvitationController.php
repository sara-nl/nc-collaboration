<?php

/**
 * Invitation controller.
 *
 */

namespace OCA\Invitation\Controller;

use Exception;
use OCA\Invitation\AppInfo\InvitationApp;
use OCA\Invitation\AppInfo\AppError;
use OCA\Invitation\Db\Schema;
use OCA\Invitation\Federation\Invitation;
use OCA\Invitation\Service\InvitationService;
use OCA\Invitation\Service\MeshRegistry\MeshRegistryService;
use OCA\Invitation\Service\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\ILogger;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class InvitationController extends Controller
{
    private InvitationService $service;
    private LoggerInterface $logger;

    public function __construct(
        IRequest $request,
        InvitationService $service,
        LoggerInterface $logger
    ) {
        parent::__construct(InvitationApp::APP_NAME, $request);
        $this->service = $service;
        $this->logger = $logger;
    }

    /**
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function index(): TemplateResponse
    {
        return new TemplateResponse($this->appName, 'invitation.index');
    }

    /**
     * Removes the notification that is associated with the invitation with specified token.
     *
     * @param string $token
     * @return void
     */
    private function removeInvitationNotification(string $token): void
    {
        $this->logger->debug(" - removing notification for invitation with token '$token'");
        try {
            $manager = \OC::$server->getNotificationManager();
            $notification = $manager->createNotification();
            $notification
                ->setApp(InvitationApp::APP_NAME)
                ->setUser(\OC::$server->getUserSession()->getUser()->getUID())
                ->setObject(MeshRegistryService::PARAM_NAME_TOKEN, $token);
            $manager->markProcessed($notification);
        } catch (Exception $e) {
            $this->logger->error('Remove notification failed: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
            throw $e;
        }
    }

    /**
     * Verify the /invite-accepted response for all required fields.
     *
     * @param array $response the response to verify
     * @return bool true if the response is valid, false otherwise
     */
    private function verifiedInviteAcceptedResponse(array $response): bool
    {
        if (!isset($response) || $response[MeshRegistryService::PARAM_NAME_USER_ID] == '') {
            $this->logger->error('/invite-accepted response does not contain the user id of the sender of the invitation.');
            return false;
        }
        if (!isset($response[MeshRegistryService::PARAM_NAME_EMAIL]) || $response[MeshRegistryService::PARAM_NAME_EMAIL] == '') {
            $this->logger->error('/invite-accepted response does not contain the email of the sender of the invitation.');
            return false;
        }
        if (!isset($response[MeshRegistryService::PARAM_NAME_NAME]) || $response[MeshRegistryService::PARAM_NAME_NAME] == '') {
            $this->logger->error('/invite-accepted response does not contain the name of the sender of the invitation.');
            return false;
        }
        return true;
    }

    /**
     * example url: https://rd-1.nl/apps/invitation/invitations?status=open,accepted
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function find(string $status = null): DataResponse
    {
        try {
            $fieldsAndValues = [];
            if (isset($status)) {
                // status param uses the OR operator
                $fieldsAndValues['status'] = explode('|', $status);
            }

            if (empty($fieldsAndValues)) {
                $this->logger->error("findAll() - missing query parameter.", ['app' => InvitationApp::APP_NAME]);
                return new DataResponse(
                    [
                        'success' => false,
                        'error_message' => AppError::REQUEST_MISSING_PARAMETER,
                    ],
                    Http::STATUS_NOT_FOUND,
                );
            }
            
            $invitations = $this->service->findAll($fieldsAndValues);
            return new DataResponse(
                [
                    'success' => true,
                    'data' => $invitations,
                ],
                Http::STATUS_OK
            );
        } catch (Exception $e) {
            $this->logger->error('invitations not found for fields: ' . print_r($status, true) . 'Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
            return new DataResponse(
                [
                    'success' => false,
                    'error_message' => AppError::ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     *
     * @NoCSRFRequired
     */
    public function findByToken(string $token = null): DataResponse
    {
        if (!isset($token)) {
            $this->logger->error("findByToken() - missing parameter 'token'.", ['app' => InvitationApp::APP_NAME]);
            return new DataResponse(
                [
                    'success' => false,
                    'error_message' => AppError::REQUEST_MISSING_PARAMETER,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
        try {
            $invitation = $this->service->findByToken($token);
            return new DataResponse(
                [
                    'success' => true,
                    'data' => $invitation->jsonSerialize(),
                ],
                Http::STATUS_OK,
            );
        } catch (NotFoundException $e) {
            $this->logger->error("invitation not found for token '$token'. Error: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
            return new DataResponse(
                [
                    'success' => false,
                    'error_message' => AppError::INVITATION_NOT_FOUND,
                ],
                Http::STATUS_NOT_FOUND,
            );
        } catch (Exception $e) {
            $this->logger->error("invitation not found for token '$token'. Error: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
            return new DataResponse(
                [
                    'success' => false,
                    'error_message' => AppError::ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Update the invitation. Only the status can be updated.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $token the token of the invitation
     * @param string $status the new status
     * @return DataResponse
     */
    public function update(string $token, string $status): DataResponse
    {
        if (!isset($token) && !isset($status)) {
            return new DataResponse(
                [
                    'success' => false,
                    'error_message' => AppError::UPDATE_INVITATION_ERROR
                ],
                Http::STATUS_NOT_FOUND,
            );
        }

        $result = $this->service->update([
            Schema::INVITATION_TOKEN => $token,
            Schema::INVITATION_STATUS => $status,
        ]);

        if (
            $status === Invitation::STATUS_DECLINED
            || $status === Invitation::STATUS_REVOKED
        ) {
            // remove potential associated notification
            $this->removeInvitationNotification($token);
        }

        if ($result === true) {
            return new DataResponse(
                [
                    'success' => true,
                    'data' => $result,
                ],
                Http::STATUS_OK,
            );
        }
        return new DataResponse(
            [
                'success' => false,
                'error_message' => AppError::UPDATE_INVITATION_ERROR
            ],
            Http::STATUS_NOT_FOUND,
        );
    }
}
