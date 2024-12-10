<?php

namespace OCA\Collaboration\Controller;

use Exception;
use OCA\Collaboration\AppInfo\AppError;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\AppInfo\Endpoints;
use OCA\Collaboration\AppInfo\InvitationError;
use OCA\Collaboration\Db\Invitation;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Service\ApplicationConfigurationException;
use OCA\Collaboration\Service\CollaborationServiceProviderService;
use OCA\Collaboration\Service\InvitationService;
use OCA\Collaboration\Service\MeshRegistry\MeshRegistryService;
use OCA\Collaboration\Service\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Template;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class CollaborationServiceProviderController extends Controller
{

    private const INVITATION_EMAIL_SUBJECT = "INVITATION_EMAIL_SUBJECT";

    public function __construct(
        IRequest $request,
        private IConfig $systemConfig,
        private IUserSession $session,
        private CollaborationServiceProviderService $collaborationServiceProviderService,
        private MeshRegistryService $meshRegistryService,
        private InvitationService $invitationService,
        private IL10N $il10n,
        private LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * Returns the properties of the this collaboration service provider.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return DataResponse ['data' => :CollaborationServiceProvider]
     */
    public function provider(): DataResponse
    {
        try {
            $uuid = $this->collaborationServiceProviderService->getUuid();
            return new DataResponse(
                [
                    'data' => $this->collaborationServiceProviderService->getProviderByUuid($uuid, false)->jsonSerialize(),
                ],
                Http::STATUS_OK,
            );
        } catch (NotFoundException $e) {
            $this->logger->error('An error occurred retrieving this provider: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::COLLABORATION_SERVICE_PROVIDER_NOT_FOUND,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Returns this' collaboration service provider services.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return DataResponse ['data' => :array]
     */
    public function services(): DataResponse
    {
        try {
            $services = $this->collaborationServiceProviderService->getServices();
            return new DataResponse(
                [
                    'data' => $services,
                ],
                Http::STATUS_OK,
            );
        } catch (NotFoundException $e) {
            $this->logger->error('An error occurred retrieving all services: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::COLLABORATION_SERVICE_PROVIDER_SERVICES_ERROR,
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
    public function createInvite(string $email = "", string $recipientName = "", string $senderName = "", string $message = ""): DataResponse
    {
        $this->logger->debug(" - CollaborationServiceProviderController::createInvite(string $email, string $recipientName, string $senderName, string $message)");
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
                    'Location' => $this->meshRegistryService->getApplicationUrl() . Endpoints::ENDPOINT_INVITATIONS . '/' . $newInvitation->getToken(),
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

    /**
     * Accepts the invition with the specified token.
     * Sets the status of the invitation to 'accepted'.
     * 
     * @param string $token the token of the invitation to accept
     * @return DataResponse
     */
    public function acceptInvite(string $token = ''): DataResponse
    {
        if ('' == $token) {
            return new DataResponse(
                [
                    'message' => InvitationError::ACCEPT_INVITE_MISSING_TOKEN,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        $invitation = null;
        try {
            $invitation = $this->invitationService->getByToken($token);
        } catch (Exception $e) {
            $this->logger->error("Unable to retrieve invitation with token '$token'. Stacktrace: " . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => InvitationError::ACCEPT_INVITE_ERROR,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        if (Invitation::STATUS_OPEN != $invitation->getStatus()) {
            return new DataResponse(
                [
                    'message' => InvitationError::ACCEPT_INVITE_NOT_OPEN,
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        $params = [
            Schema::INVITATION_TOKEN => $token,
            Schema::INVITATION_STATUS => Invitation::STATUS_ACCEPTED,
        ];
        $result = $this->invitationService->update($params);
        if ($result === true) {
            $invitation = null;
            try {
                $invitation = $this->invitationService->getByToken($token);
            } catch (Exception $e) {
                $this->logger->error("acceptInvite failed, invitation not found for token '$token' after updating. Error: " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
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
                'message' => InvitationError::UPDATE_INVITATION_ERROR,
            ],
            Http::STATUS_NOT_FOUND,
        );
    }

    // /**
    //  * Handles a received invite, ie., sets the invite to the specified status
    //  * 
    //  * @param string $token the token of the received invite
    //  * @param string $providerUuid the uuid of the provider from which the invite was sent
    //  * @param string $providerDomain the domain of the provider from which the intie was sent
    //  * @param string $status the desired status to which the invite should be set
    //  * @return DataResponse
    //  */
    // public function handleInvite(string $token = '', string $providerUuid = '', string $providerDomain = '', string $status = ''): DataResponse
    // {
    //     if ('' == trim($token)) {
    //         $this->logger->error('Invite is missing the token.', ['app' => Application::APP_ID]);
    //         // TODO redirect to error page
    //         return new DataResponse(
    //             [
    //                 'message' => InvitationError::HANDLE_INVITE_MISSING_TOKEN,
    //             ],
    //             Http::STATUS_NOT_FOUND
    //         );
    //     }
    //     // if ('' == trim($providerUuid) && '' == trim($providerDomain)) {
    //     //     $this->logger->error('Invite is missing sender provider.', ['app' => Application::APP_ID]);
    //     //     return new TemplateResponse(
    //     //         $this->appName,
    //     //         'wayf.error',
    //     //         ['message' => MeshRegistryError::FORWARD_INVITE_MISSING_PROVIDER],
    //     //         'blank',
    //     //         Http::STATUS_NOT_FOUND,
    //     //     );
    //     // }
    //     // $provider = $this->findProvider($providerUuid, $providerDomain);
    //     // if(!isset($provider)) {
    //     //     $this->logger->error('Invite is missing sender provider.', ['app' => Application::APP_ID]);
    //     //     return new TemplateResponse(
    //     //         $this->appName,
    //     //         'wayf.error',
    //     //         ['message' => MeshRegistryError::FORWARD_INVITE_PROVIDER_NOT_FOUND],
    //     //         'blank',
    //     //         Http::STATUS_NOT_FOUND,
    //     //     );
    //     // }

    //     // TODO redirect to appropriate invitation page where from the invitation can be handled further
    //     // return new RedirectResponse($this->urlGenerator->linkToRoute($this->appName . '.invitation.invitations.getByToken', ['token' => $token]));
    //     return new DataResponse(
    //         [
    //             'data' => 
    //         ],
    //         Http::STATUS_OK,
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
}
