<?php

/**
 * Invitation controller.
 *
 */

namespace OCA\Collaboration\Controller;

use Exception;
use OCA\Collaboration\AppInfo\AppError;
use OCA\Collaboration\AppInfo\Application;
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
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Template;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class InvitationController extends Controller
{
    public const ENDPOINT_INVITATIONS = '/invitations';
    public const ENDPOINT_HANDLE_INVITE = '/handle-invite';
    private const INVITATION_EMAIL_SUBJECT = "INVITATION_EMAIL_SUBJECT";

    public function __construct(
        IRequest $request,
        private IConfig $systemConfig,
        private IUserSession $session,
        private InvitationService $invitationService,
        private CollaborationServiceProviderService $collaborationServiceProviderService,
        private MeshRegistryService $meshRegistryService,
        private IURLGenerator $urlGenerator,
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
        } catch (NotFoundException $e) {
            $this->logger->error("invitation not found for token '$token'. Error: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => InvitationError::INVITATION_NOT_FOUND,
                ],
                Http::STATUS_NOT_FOUND,
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
     * Creates an invition and sends it to the specified email address.
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
        $this->logger->debug(" - InvitationController::createInvitation(string $email, string $recipientName, string $senderName, string $message)");
        if ("" == $email) {
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_NO_RECIPIENT_EMAIL,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if ("" == $recipientName) {
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_NO_RECIPIENT_NAME,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if ("" == $senderName) {
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_NO_SENDER_NAME,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_EMAIL_INVALID,
                ],
                Http::STATUS_NOT_FOUND
            );
        }

        // check pre-conditions
        $preConditionFailed = $this->generateInvitePreCondition();
        if ($preConditionFailed->getStatus() != Http::STATUS_OK) {
            return $preConditionFailed;
        }
        $email === $this->session->getUser()->getEMailAddress();

        $inviteLink = '';
        try {
            // generate the token
            $token = Uuid::uuid4()->toString();

            // TODO get this provider's uuid
            $provider = $this->collaborationServiceProviderService->getProviderByUuid($this->collaborationServiceProviderService->getUuid());
            $params = [
                MeshRegistryService::PARAM_NAME_TOKEN => $token,
                MeshRegistryService::PARAM_NAME_PROVIDER_UUID => $provider->getUuid(),
                MeshRegistryService::PARAM_NAME_PROVIDER_DOMAIN => $provider->getDomain(),
            ];

            // Check for existing open and accepted invitations for the same recipient email
            // Note that accepted invitations might have another recipient's email set, so there might still already be an existing invitation
            // but this should be dealt with upon acceptance of this new invitation
            $fieldsAndValues = [
                Schema::INVITATION_STATUS => [Invitation::STATUS_OPEN, Invitation::STATUS_ACCEPTED],
                Schema::INVITATION_RECIPIENT_EMAIL => [$email]
            ];

            $invitations = $this->invitationService->findAll($fieldsAndValues);
            if (count($invitations) > 0) {
                $this->logger->debug('An invitation already exists for the given parameters');
                return new DataResponse(
                    [
                        "message" => InvitationError::CREATE_INVITATION_EXISTS,
                    ],
                    Http::STATUS_NOT_FOUND,
                );
            }

            $inviteLink = $this->meshRegistryService->inviteLink($params);
        } catch (ApplicationConfigurationException $e) {
            $this->logger->error("An error has occurred: " . $e->getMessage() . " Stacktrace: " . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::APPLICATION_CONFIGURATION_EXCEPTION,
                ],
                Http::STATUS_NOT_FOUND,
            );
        } catch (Exception $e) {
            $this->logger->error("An error has occurred: " . $e->getMessage() . " Stacktrace: " . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }

        // persist the invite to send
        $invitation = new Invitation();
        $invitation->setUid($this->session->getUser()->getUID());
        $invitation->setToken($token);
        $invitation->setProviderDomain('my-domain');
        $invitation->setSenderCloudId($this->session->getUser()->getCloudId());
        $invitation->setSenderEmail($this->session->getUser()->getEMailAddress());
        $invitation->setSenderName($senderName);
        $invitation->setRecipientEmail($email);
        $invitation->setTimestamp(time());
        $invitation->setStatus(Invitation::STATUS_NEW);

        try {
            $mailer = \OC::$server->getMailer();
            $mail = $mailer->createMessage();
            $mail->setSubject($this->il10n->t(self::INVITATION_EMAIL_SUBJECT));
            $mail->setFrom([$this->getEmailFromAddress('invitation-no-reply')]);
            $mail->setTo(array($email => $email));
            $language = 'en'; // actually not used, the email itself is multi language
            $htmlText = $this->getMailBody($inviteLink, $recipientName, $message, 'html', $language);
            $mail->setHtmlBody($htmlText);
            $plainText = $this->getMailBody($inviteLink, $recipientName, $message, 'text', $language);
            $mail->setPlainBody($plainText);
            $failedRecipients = $mailer->send($mail);
            if (sizeof($failedRecipients) > 0) {
                $this->logger->error(' - failed recipients: ' . print_r($failedRecipients, true), ['app' => Application::APP_ID]);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            // TODO Instead of failing, we could continue and still insert and display the invitation as failed in the list
            // this would probably work best with a modify and resend option

            // So just continue for now
        }

        // when all's well set status to open and persist
        $invitation->setStatus(Invitation::STATUS_OPEN);
        try {
            $newInvitation = $this->invitationService->insert($invitation);
        } catch (Exception $e) {
            $this->logger->error('An error occurred while generating the invite: ' . $e->getMessage(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_ERROR,
                ],
                Http::STATUS_NOT_FOUND
            );
        }

        if (isset($newInvitation) && $newInvitation->getId() > 0) {
            return new DataResponse(
                [
                    'data' => $newInvitation->jsonSerialize(),
                ],
                Http::STATUS_CREATED,
                [
                    'Location' => $this->meshRegistryService->getApplicationUrl() . self::ENDPOINT_INVITATIONS . '/' . $newInvitation->getToken(),
                    'InviteLink' => $inviteLink,
                ],
            );
        }
        $this->logger->error("Create invitation failed with no further info.", ['app' => Application::APP_ID]);
        return new DataResponse(
            [
                'message' => InvitationError::CREATE_INVITATION_ERROR
            ],
            Http::STATUS_NOT_FOUND
        );
    }

    public function handleInvite(string $token = '', string $providerUuid = '', string $providerDomain = ''): RedirectResponse|DataResponse
    {
        if ('' == trim($token)) {
            $this->logger->error('Invite is missing the token.', ['app' => Application::APP_ID]);
            // TODO redirect to error page
            return new DataResponse(
                [
                    'message' => InvitationError::HANDLE_INVITE_MISSING_TOKEN,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        // if ('' == trim($providerUuid) && '' == trim($providerDomain)) {
        //     $this->logger->error('Invite is missing sender provider.', ['app' => Application::APP_ID]);
        //     return new TemplateResponse(
        //         $this->appName,
        //         'wayf.error',
        //         ['message' => MeshRegistryError::FORWARD_INVITE_MISSING_PROVIDER],
        //         'blank',
        //         Http::STATUS_NOT_FOUND,
        //     );
        // }
        // $provider = $this->findProvider($providerUuid, $providerDomain);
        // if(!isset($provider)) {
        //     $this->logger->error('Invite is missing sender provider.', ['app' => Application::APP_ID]);
        //     return new TemplateResponse(
        //         $this->appName,
        //         'wayf.error',
        //         ['message' => MeshRegistryError::FORWARD_INVITE_PROVIDER_NOT_FOUND],
        //         'blank',
        //         Http::STATUS_NOT_FOUND,
        //     );
        // }

        // TODO redirect to appropriate invitation page where from the invitation can be handled further

        return new RedirectResponse($this->urlGenerator->linkToRoute($this->appName . '.invitation.invitations.getByToken', ['token' => $token]));
    }

    /**
     * Accept the received invitation with the specified token.
     *
     * @param string $token the token
     * @return DataResponse if successfull, echoes the invitation token
     */
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
    // public function update(string $token, string $status): DataResponse
    // {
    //     if (!isset($token) && !isset($status)) {
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::UPDATE_INVITATION_ERROR
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }

    //     if (Invitation::STATUS_ACCEPTED === $status) {
    //         return $this->acceptInvite($token);
    //     }

    //     $result = $this->invitationService->update([
    //         Schema::INVITATION_TOKEN => $token,
    //         Schema::INVITATION_STATUS => $status,
    //     ]);

    //     if (
    //         $status === Invitation::STATUS_DECLINED
    //         || $status === Invitation::STATUS_REVOKED
    //     ) {
    //         // remove potential associated notification
    //         $this->removeInvitationNotification($token);
    //     }

    //     if ($result === true) {
    //         return new DataResponse(
    //             [
    //                 'data' => [
    //                     "token" => $token,
    //                     "status" => $status
    //                 ],
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     }
    //     return new DataResponse(
    //         [
    //             'message' => AppError::UPDATE_INVITATION_ERROR
    //         ],
    //         Http::STATUS_NOT_FOUND,
    //     );
    // }

    /**
     * Returns a DataResponse with an error why the precondition failed,
     * or null when it hasn't.
     */
    private function generateInvitePreCondition(): DataResponse
    {
        $_userEmail = $this->session->getUser()->getEMailAddress();
        if (!isset($_userEmail) || $_userEmail === '') {
            return new DataResponse(
                [
                    'message' => InvitationError::CREATE_INVITATION_ERROR_SENDER_EMAIL_MISSING,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
        return new DataResponse(
            [
                'success' => true,
            ],
            Http::STATUS_OK,
        );
    }

    /**
     * Get the email from address.
     * Can be explicitly set using system config: 'invitation_mail_from_address'.
     * Otherwise uses the default config which uses the optional system config 'mail_from_address' and 'mail_domain' keys.
     *
     * @param string $address the address part in 'address@maildomain.com'
     * @return string
     */
    private function getEmailFromAddress(string $address = null)
    {
        if (empty($address)) {
            $address = 'no-reply';
        }
        $senderAddress = Util::getDefaultEmailAddress($address);
        return $this->systemConfig->getSystemValue('invitation_mail_from_address', $senderAddress);
    }

    /**
     * Returns the mail body rendered according to the specified target template.
     * @param string $inviteLink the invite link
     * @param string $recipientName the name of the recipient
     * @param string $message additional message to render
     * @param string $targetTemplate on of 'html', 'text'
     * @param string $languageCode the language code to use
     * @return string the rendered body
     */
    private function getMailBody(string $inviteLink, string $recipientName, string $message, string $targetTemplate = 'html', string $languageCode = '')
    {
        $tmpl = new Template(Application::APP_ID, "mail/$targetTemplate", '', false, $languageCode);
        $tmpl->assign('recipientName', $recipientName);
        $tmpl->assign('fromName', $this->session->getUser()->getDisplayName());
        $tmpl->assign('inviteLink', $inviteLink);
        $tmpl->assign('message', $message);
        return $tmpl->fetchPage();
    }

    /**
     * Removes the notification that is associated with the invitation with specified token.
     *
     * @param string $token
     * @return void
     */
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
