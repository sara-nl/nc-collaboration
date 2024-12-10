<?php

/**
 * Invitation controller.
 *
 */

namespace OCA\Collaboration\Controller;

use Exception;
use OCA\Collaboration\AppInfo\AppError;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\AppInfo\Endpoints;
use OCA\Collaboration\AppInfo\InvitationError;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Db\Invitation;
use OCA\Collaboration\Service\ApplicationConfigurationException;
use OCA\Collaboration\Service\CollaborationServiceProviderService;
use OCA\Collaboration\Service\InvitationService;
use OCA\Collaboration\Service\MeshRegistry\MeshRegistryService;
use OCA\Collaboration\Service\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Template;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

use function PHPUnit\Framework\isEmpty;

class InvitationController extends Controller
{
    public function __construct(
        IRequest $request,
        private IUserSession $session,
        private InvitationService $invitationService,
        private CollaborationServiceProviderService $collaborationServiceProviderService,
        private MeshRegistryService $meshRegistryService,
        private IL10N $il10n,
        private LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * Route: GET /invitation/{token}
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getByToken(string $token = null): DataResponse
    {
        if (!isset($token)) {
            return new DataResponse(
                [
                    'message' => AppError::REQUEST_MISSING_PARAMETER,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
        try {
            $invitation = $this->invitationService->getByToken($token);
            return new DataResponse(
                [
                    'data' => $invitation->jsonSerialize(),
                ],
                Http::STATUS_OK,
            );
        } catch (Exception $e) {
            $this->logger->error("invitation not found for token '$token'. Error: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Route: GET /invitations
     * Example:
     *  https://rd-1.nl/apps/collaboration/invitation-service/invitations?status=open|accepted
     * Available parameter value operators:
     *  | or
     * If multiple parameters are specified they all must apply (logical AND).
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function find(string $status = ""): DataResponse
    {
        try {
            $fieldsAndValues = [];
            if ($status != "") {
                $fieldsAndValues['status'] = explode('|', $status);
            }

            if (empty($fieldsAndValues)) {
                return new DataResponse(
                    [
                        'message' => AppError::REQUEST_MISSING_PARAMETER,
                    ],
                    Http::STATUS_NOT_FOUND,
                );
            }

            $invitations = $this->invitationService->findAll($fieldsAndValues);
            $this->logger->debug(" found invitations: " . print_r($invitations, true));

            return new DataResponse(
                [
                    'data' => $invitations,
                ],
                Http::STATUS_OK
            );
        } catch (Exception $e) {
            $this->logger->error('invitations not found for fields: ' . print_r($status, true) . 'Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Creates and persist an invitation.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param array the invitation as associative array
     * @return DataResponse the result
     */
    public function newInvitation(array $params): DataResponse
    {
        try {
            // TODO check required parameters
            $params[Schema::INVITATION_USER_ID] = $this->session->getUser()->getUID();
            $invitation = $this->invitationService->insert(Invitation::fromParams($params));

            return new DataResponse(
                [
                    'data' => $invitation->jsonSerialize()
                ],
                Http::STATUS_CREATED,
                [
                    'Location' => $this->meshRegistryService->getApplicationUrl() . Endpoints::ENDPOINT_INVITATIONS . '/' . $invitation->getToken()
                ]
            );
        } catch (Exception $e) {
            $this->logger->error('Could not create insert invitation: ' . $e->getTraceAsString());
            return new DataResponse(
                ['message' => InvitationError::NEW_INVITATION_ERROR],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * Update the invitation.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $token the token of the invitation
     * @param array $params associative array of properties to patch
     * @return DataResponse
     */
    public function update(string $token = '', array $params = []): DataResponse
    {
        $this->logger->debug(" - update($token, " . print_r($params, true));
        if ('' == $token) {
            return new DataResponse(
                [
                    'message' => InvitationError::UPDATE_INVITATION_ERROR_TOKEN_NOT_PROVIDED
                ],
                Http::STATUS_NOT_FOUND,
            );
        }

        $result = $this->invitationService->update($params);

        if ($result === true) {
            $invitation = null;
            try {
                $invitation = $this->invitationService->getByToken($token);
            } catch (Exception $e) {
                $this->logger->error("invitation not found for token '$token'. Error: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
                return new DataResponse(
                    [
                        'message' => InvitationError::UPDATE_INVITATION_ERROR,
                    ],
                    Http::STATUS_NOT_FOUND,
                );
            }
            return new DataResponse(
                [
                    'data' => $invitation->jsonSerialize(),
                ],
                Http::STATUS_OK,
            );
        }
        return new DataResponse(
            [
                'message' => InvitationError::UPDATE_INVITATION_ERROR
            ],
            Http::STATUS_NOT_FOUND,
        );
    }

    // /**
    //  * Accept the received invitation with the specified token.
    //  *
    //  * @param string $token the token
    //  * @return DataResponse if successfull, echoes the invitation token
    //  */
    // private function acceptInvite(string $token = ''): DataResponse
    // {
    //     try {
    //         if ($token == '') {
    //             $this->logger->error('acceptInvite: missing parameter token.', ['app' => Application::APP_ID]);
    //             return new DataResponse(
    //                 [
    //                     'message' => AppError::REQUEST_MISSING_PARAMETER
    //                 ],
    //                 Http::STATUS_NOT_FOUND
    //             );
    //         }

    //         $invitation = null;
    //         try {
    //             $invitation = $this->invitationService->getByToken($token);
    //         } catch (NotFoundException $e) {
    //             $this->logger->error("acceptInvite: invitation not found for token '$token'", ['app' => Application::APP_ID]);
    //             return new DataResponse(
    //                 [
    //                     'message' => AppError::INVITATION_NOT_FOUND
    //                 ],
    //                 Http::STATUS_NOT_FOUND
    //             );
    //         }

    //         // check pre-conditions
    //         $preConditionFailed = $this->acceptInvitePreCondition();
    //         if ($preConditionFailed->getStatus() != Http::STATUS_OK) {
    //             return $preConditionFailed;
    //         }

    //         $recipientEndpoint = $this->meshRegistryService->getEndpoint();
    //         $recipientCloudID = $this->session->getUser()->getCloudId();
    //         $recipientEmail = $this->session->getUser()->getEMailAddress();
    //         $recipientName = $this->session->getUser()->getDisplayName();
    //         $params = [
    //             MeshRegistryService::PARAM_NAME_RECIPIENT_PROVIDER => $recipientEndpoint,
    //             MeshRegistryService::PARAM_NAME_TOKEN => $token,
    //             MeshRegistryService::PARAM_NAME_USER_ID => $recipientCloudID,
    //             MeshRegistryService::PARAM_NAME_EMAIL => $recipientEmail,
    //             MeshRegistryService::PARAM_NAME_NAME => $recipientName,
    //         ];

    //         $url = $this->meshRegistryService->getFullInviteAcceptedEndpointURL($invitation->getProviderEndpoint());
    //         $httpClient = new HttpClient($this->logger);
    //         $response = $httpClient->curlPost($url, $params);

    //         if (isset($response['success']) && $response['success'] == false) {
    //             $this->logger->error('Failed to accept the invitation: /invite-accepted failed with response: ' . print_r($response, true), ['app' => Application::APP_ID]);
    //             return new DataResponse(
    //                 [
    //                     'message' => (isset($response['error_message']) ? $response['error_message'] : AppError::HANDLE_INVITATION_ERROR)
    //                 ],
    //                 Http::STATUS_NOT_FOUND
    //             );
    //         }
    //         // note: beware of the format of response of the OCM call, it has no 'data' field
    //         if ($this->verifiedInviteAcceptedResponse($response) == false) {
    //             $this->logger->error('Failed to accept the invitation - returned fields not valid: ' . print_r($response, true), ['app' => Application::APP_ID]);
    //             return new DataResponse(
    //                 [
    //                     'message' => AppError::HANDLE_INVITATION_OCM_INVITE_ACCEPTED_RESPONSE_FIELDS_INVALID
    //                 ],
    //                 Http::STATUS_NOT_FOUND
    //             );
    //         }

    //         // withdraw any previous accepted invitation from the same inviter
    //         $existingInvitationsReceived = $this->invitationService->findAll([
    //             [Schema::VINVITATION_SENDER_CLOUD_ID => $response[MeshRegistryService::PARAM_NAME_USER_ID]],
    //             [Schema::VINVITATION_RECIPIENT_CLOUD_ID => $recipientCloudID],
    //             [Schema::VINVITATION_STATUS => Invitation::STATUS_ACCEPTED],
    //         ]);
    //         $existingInvitationsSent = $this->invitationService->findAll([
    //             [Schema::VINVITATION_RECIPIENT_CLOUD_ID => $response[MeshRegistryService::PARAM_NAME_USER_ID]],
    //             [Schema::VINVITATION_SENDER_CLOUD_ID => $recipientCloudID],
    //             [Schema::VINVITATION_STATUS => Invitation::STATUS_ACCEPTED],
    //         ]);
    //         $existingInvitations = array_merge($existingInvitationsReceived, $existingInvitationsSent);
    //         if (count($existingInvitations) > 0) {
    //             foreach ($existingInvitations as $existingInvitation) {
    //                 $this->logger->debug("A previous invitation for remote user with name " . $response[MeshRegistryService::PARAM_NAME_NAME] . " was accepted already. Withdrawing that one", ['app' => Application::APP_ID]);
    //                 $updateResult = $this->invitationService->update([
    //                     Schema::INVITATION_TOKEN => $existingInvitation->getToken(),
    //                     Schema::INVITATION_STATUS => Invitation::STATUS_WITHDRAWN,
    //                 ]);
    //                 if ($updateResult == false) {
    //                     return new DataResponse(
    //                         [
    //                             'message' => AppError::ACCEPT_INVITE_ERROR,
    //                         ],
    //                         Http::STATUS_NOT_FOUND,
    //                     );
    //                 }
    //             }
    //         }

    //         // all's well, update the open invitation
    //         $updateResult = $this->invitationService->update(
    //             [
    //                 Schema::INVITATION_TOKEN => $invitation->getToken(),
    //                 Schema::INVITATION_SENDER_CLOUD_ID => $response[MeshRegistryService::PARAM_NAME_USER_ID],
    //                 Schema::INVITATION_SENDER_EMAIL => $response[MeshRegistryService::PARAM_NAME_EMAIL],
    //                 Schema::INVITATION_SENDER_NAME => $response[MeshRegistryService::PARAM_NAME_NAME],
    //                 Schema::INVITATION_STATUS => Invitation::STATUS_ACCEPTED,
    //             ],
    //             true
    //         );
    //         if ($updateResult == false) {
    //             $this->logger->error("Failed to handle /accept-invite (invitation with token '$token' could not be updated).", ['app' => Application::APP_ID]);
    //             return new DataResponse(
    //                 [
    //                     'message' => AppError::ACCEPT_INVITE_ERROR,
    //                 ],
    //                 Http::STATUS_NOT_FOUND
    //             );
    //         }

    //         $this->removeInvitationNotification($token);

    //         return new DataResponse(
    //             [
    //                 'data' => [
    //                     "token" => $token,
    //                     "status" => Invitation::STATUS_ACCEPTED
    //                 ],
    //             ],
    //             Http::STATUS_OK
    //         );
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app]' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::ACCEPT_INVITE_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Removes the notification that is associated with the invitation with specified token.
    //  *
    //  * @param string $token
    //  * @return void
    //  */
    // private function removeInvitationNotification(string $token): void
    // {
    //     $this->logger->debug(" - removing notification for invitation with token '$token'");
    //     try {
    //         $manager = \OC::$server->getNotificationManager();
    //         $notification = $manager->createNotification();
    //         $notification
    //             ->setApp(Application::APP_ID)
    //             ->setUser($this->session->getUser()->getUID())
    //             ->setObject(MeshRegistryService::PARAM_NAME_TOKEN, $token);
    //         $manager->markProcessed($notification);
    //     } catch (Exception $e) {
    //         $this->logger->error('Remove notification failed: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => InvitationApp::APP_NAME]);
    //         throw $e;
    //     }
    // }

    /**
     * Returns a DataResponse with an error why the precondition failed,
     * or null when it hasn't.
     */
    // private function acceptInvitePreCondition(): DataResponse
    // {
    //     $_userEmail = $this->session->getUser()->getEMailAddress();
    //     if (!isset($_userEmail) || $_userEmail === '') {
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::ACCEPT_INVITE_ERROR_RECIPIENT_EMAIL_MISSING,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    //     $_userName = $this->session->getUser()->getDisplayName();
    //     if (!isset($_userName) || $_userName === '') {
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::ACCEPT_INVITE_ERROR_RECIPIENT_NAME_MISSING,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    //     return new DataResponse(
    //         [],
    //         Http::STATUS_OK,
    //     );
    // }

    /**
     * Verify the /invite-accepted response for all required fields.
     *
     * @param array $response the response to verify
     * @return bool true if the response is valid, false otherwise
     */
    // private function verifiedInviteAcceptedResponse(array $response): bool
    // {
    //     if (!isset($response) || $response[MeshRegistryService::PARAM_NAME_USER_ID] == '') {
    //         $this->logger->error('/invite-accepted response does not contain the user id of the sender of the invitation.');
    //         return false;
    //     }
    //     if (!isset($response[MeshRegistryService::PARAM_NAME_EMAIL]) || $response[MeshRegistryService::PARAM_NAME_EMAIL] == '') {
    //         $this->logger->error('/invite-accepted response does not contain the email of the sender of the invitation.');
    //         return false;
    //     }
    //     if (!isset($response[MeshRegistryService::PARAM_NAME_NAME]) || $response[MeshRegistryService::PARAM_NAME_NAME] == '') {
    //         $this->logger->error('/invite-accepted response does not contain the name of the sender of the invitation.');
    //         return false;
    //     }
    //     return true;
    // }
}
