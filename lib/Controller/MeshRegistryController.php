<?php

/**
 * This is the mesh registry controller.
 *
 */

namespace OCA\Collaboration\Controller;

use Exception;
use OC\AppFramework\App;
use OCA\Collaboration\AppInfo\AppError;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\AppInfo\MeshRegistryError;
use OCA\Collaboration\Db\CollaborationServiceProvider;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\HttpClient;
use OCA\Collaboration\Service\ApplicationConfigurationException;
use OCA\Collaboration\Service\CollaborationServiceProviderService;
use OCA\Collaboration\Service\MeshRegistry\MeshRegistryService;
use OCA\Collaboration\Service\NotFoundException;
use OCA\Collaboration\Service\ServiceException;
use OCA\Invitation\AppInfo\Application as AppInfoApplication;
use OCP\App\IAppManager;
use OCP\AppFramework\App as AppFrameworkApp;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\Template\Template;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class MeshRegistryController extends Controller
{
    /** @var IAppConfig */


    public function __construct(
        IRequest $request,
        private IAppConfig $appConfig,
        private MeshRegistryService $meshRegistryService,
        private CollaborationServiceProviderService $collaborationServiceProviderService,
        private IURLGenerator $urlGenerator,
        private LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * Returns all registered collaboration service providers.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return DataResponse ['data' => [:CollaborationServiceProviderService](an array of CollaborationServiceProviderService objects)]
     */
    public function providers(): DataResponse
    {
        try {
            $this->logger->debug(' - pathInfo: ' . $this->request->getPathInfo() . ', - rawPathInfo: ' . $this->request->getRawPathInfo() . ', - serverHost: ' . $this->request->getServerHost() . ' - requestUri' . $this->request->getRequestUri());
            $this->logger->debug(' - appConfig: ' . print_r($this->appConfig->getAllValues(Application::APP_ID), true));
            $providers = $this->meshRegistryService->allProviders();
            return new DataResponse(
                [
                    'data' => $providers,
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error('An error occurred retrieving all providers: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_PROVIDERS_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Provides the caller with a list (WAYF page) of mesh EFSS invitation service providers to choose from.

     * @NoCSRFRequired
     * @PublicPage
     *
     * @param string $token the invitation token
     * @param string $uuid the uuid of the sending provider
     * @param string $providerDomain the endpoint of the sender
     * @param string $name the name of the sender
     * @return Response
     */
    public function forwardInvite(string $token = '', string $providerUuid = '', string $providerDomain = ''): TemplateResponse|RedirectResponse
    {
        if ('' == trim($token)) {
            $this->logger->error('Invite is missing the token.', ['app' => Application::APP_ID]);
            return new TemplateResponse(
                $this->appName,
                'wayf.error',
                ['message' => MeshRegistryError::FORWARD_INVITE_MISSING_TOKEN],
                'blank',
                Http::STATUS_NOT_FOUND,
            );
        }
        if ('' == trim($providerUuid) && '' == trim($providerDomain)) {
            $this->logger->error('Invite is missing sender provider.', ['app' => Application::APP_ID]);
            return new TemplateResponse(
                $this->appName,
                'wayf.error',
                ['message' => MeshRegistryError::FORWARD_INVITE_MISSING_PROVIDER],
                'blank',
                Http::STATUS_NOT_FOUND,
            );
        }
        $provider = $this->findProvider($providerUuid, $providerDomain);
        if (!isset($provider)) {
            $this->logger->error('Invite is missing sender provider.', ['app' => Application::APP_ID]);
            return new TemplateResponse(
                $this->appName,
                'wayf.error',
                ['message' => MeshRegistryError::FORWARD_INVITE_PROVIDER_NOT_FOUND],
                'blank',
                Http::STATUS_NOT_FOUND,
            );
        }

        // TODO display real WAYF page

        // show WAYF page
        return new TemplateResponse(
            $this->appName,
            'wayf',
            ['message' => "WAYF page"],
            'blank',
            Http::STATUS_OK,
        );
    }

    /**
     * Returns the Collaboration Service Provider with the specified uuid and/or domain.
     * Ie. if both are specified the provider must match both.
     */
    private function findProvider(string $providerUuid = '', string $providerDomain = ''): CollaborationServiceProvider|null
    {
        $criteria = [];
        if ('' != trim($providerUuid)) {
            $criteria[Schema::COLLABORATION_SERVICE_PROVIDER_UUID] = [$providerUuid];
        }
        if ('' != trim($providerDomain)) {
            $criteria[Schema::COLLABORATION_SERVICE_PROVIDER_DOMAIN] = [$providerDomain];
        }
        $result = $this->collaborationServiceProviderService->findAll($criteria, false);
        if (count($result) == 1) {
            return $result[0];
        }
        return null;
    }

    // /**
    //  * Displays the WAYF error page.
    //  *
    //  * @NoCSRFRequired
    //  * @PublicPage
    //  * @param array $params Error page parameters. Keys: 'message'
    //  * @return void
    //  */
    // public function wayfError(string $message, int $status = Http::STATUS_OK): TemplateResponse
    // {
    //     return new TemplateResponse(
    //         $this->appName,
    //         'wayf.error',
    //         ['message' => $message],
    //         'blank',
    //         isset($status) ? $status : Http::STATUS_OK
    //     );
    // }

    // /**
    //  * Displays the WAYF page.
    //  *
    //  * @NoCSRFRequired
    //  * @PublicPage
    //  * @param string $token the token
    //  * @param string $providerEndpoint the endpoint of the sender
    //  * @return
    //  */
    // public function wayf(string $token, string $providerEndpoint): void
    // {
    //     try {
    //         $wayfItems = $this->getWayfItems($token, $providerEndpoint);
    //         if (sizeof($wayfItems) == 0) {
    //             throw new ServiceException(AppError::WAYF_NO_PROVIDERS_FOUND);
    //         }
    //         $l = \OC::$server->getL10NFactory()->findLanguage(CollaborationApp::APP_NAME);
    //         $tmpl = new Template('collaboration', "wayf/wayf", '', false, $l);
    //         $tmpl->assign('wayfItems', $wayfItems);
    //         echo $tmpl->fetchPage();
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => CollaborationApp::APP_NAME]);
    //         $html = '<div>' . $e->getMessage() . '</div></html>';
    //         echo $html;
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => CollaborationApp::APP_NAME]);
    //         $html = '<div>' . AppError::WAYF_ERROR . '</div></html>';
    //         echo $html;
    //     }
    //     exit(0);
    // }

    // /**
    //  * Returns the properties of the this invitation service provider.
    //  *
    //  * @PublicPage
    //  * @NoCSRFRequired
    //  *
    //  * @return DataResponse ['data' => :InvitationServiceProvider]
    //  */
    // public function invitationServiceProvider(): DataResponse
    // {
    //     try {
    //         return new DataResponse(
    //             [
    //                 'data' => $this->meshRegistryService->getInvitationServiceProvider()->jsonSerialize(),
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (NotFoundException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_GET_PROVIDER_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Updates this instance's invitation service provider properties.
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @param string $endpoint
    //  * @param string $name
    //  * @return DataResponse
    //  */
    // public function updateInvitationServiceProvider(string $endpoint, string $name): DataResponse
    // {
    //     try {
    //         $fieldsArray = ['endpoint' => $endpoint, 'name' => $name];

    //         $endpoint = "";
    //         try {
    //             $endpoint = $this->meshRegistryService->getEndpoint();
    //         } catch (ApplicationConfigurationException $e) {
    //             // no endpoint yet, this is the initialization of this instances provider
    //         }

    //         // check the endpoint connection
    //         $url = $this->meshRegistryService->getFullInvitationServiceProviderEndpointUrl($fieldsArray['endpoint']);
    //         $httpClient = new HttpClient($this->logger);
    //         $response = $httpClient->curlGet($url);
    //         if ($response['success'] == false) {
    //             $this->logger->error('Failed to call ' . MeshRegistryService::ENDPOINT_INVITATION_SERVICE_PROVIDER . " on endpoint '$endpoint'. Response: " . print_r($response, true), ['app' => Application::APP_ID]);
    //             throw new ServiceException("Failed to call endpoint '$endpoint'");
    //         }

    //         $isp = $this->meshRegistryService->updateInvitationServiceProvider($endpoint, $fieldsArray);

    //         return new DataResponse(
    //             [
    //                 'data' => $isp->jsonSerialize(),
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage() . " Trace: " . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_SET_ENDPOINT_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Adds a new invitation service provider with the specified endpoint.
    //  * The properties of the provider will be requested through the specified endpoint.
    //  * If this fails an HTTP error will be returned.
    //  *
    //  * Note: if the provider already exists it's properties will become updated
    //  * through the remote provider /registry/invitation-service-provider call.
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @param string $endpoint the endpoint of the new invitation service provider
    //  * @return DataResponse [ ..., 'data' => :InvitationServiceProvider ]
    //  */
    // public function addInvitationServiceProvider(string $endpoint): DataResponse
    // {
    //     try {
    //         // some sanitizing
    //         $endpoint = trim(trim($endpoint), '/');

    //         if ($endpoint === $this->meshRegistryService->getEndpoint()) {
    //             return new DataResponse(
    //                 [
    //                     'message' => AppError::SETTINGS_ADD_PROVIDER_IS_NOT_REMOTE_ERROR,
    //                 ],
    //                 Http::STATUS_NOT_FOUND,
    //             );
    //         }

    //         // check whether the provider is not already registered
    //         try {
    //             $provider = $this->meshRegistryService->findInvitationServiceProvider($endpoint);
    //             $this->logger->error("The provider with endpoint $endpoint is already registered", ['app' => Application::APP_ID]);
    //             return new DataResponse(
    //                 [
    //                     'message' => AppError::MESH_REGISTRY_ADD_PROVIDER_EXISTS_ERROR,
    //                 ],
    //                 Http::STATUS_NOT_FOUND,
    //             );
    //         } catch (NotFoundException $e) {
    //             // all good
    //         }

    //         $url = $this->meshRegistryService->getFullInvitationServiceProviderEndpointUrl($endpoint);
    //         $httpClient = new HttpClient($this->logger);
    //         $response = $httpClient->curlGet($url);
    //         if ($response['success'] == false) {
    //             $this->logger->error('Failed to call ' . MeshRegistryService::ENDPOINT_INVITATION_SERVICE_PROVIDER . " on endpoint '$endpoint'. Response: " . print_r($response, true), ['app' => Application::APP_ID]);
    //             throw new ServiceException("Failed to call endpoint '$endpoint'");
    //         }

    //         $data = (array)$response['data'];
    //         $verified = $this->verifyInvitationServiceProviderResponse($data);
    //         $this->logger->debug(print_r($data, true));
    //         if ($verified === true) {
    //             $invitationServiceProvider = new InvitationServiceProvider();
    //             $invitationServiceProvider->setEndpoint($data[Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT]);
    //             $invitationServiceProvider->setDomain($data[Schema::INVITATION_SERVICE_PROVIDER_DOMAIN]);
    //             $invitationServiceProvider->setName($data[Schema::INVITATION_SERVICE_PROVIDER_NAME]);

    //             $invitationServiceProvider = $this->meshRegistryService->addInvitationServiceProvider($invitationServiceProvider);

    //             return new DataResponse(
    //                 [
    //                     'data' => $invitationServiceProvider->jsonSerialize(),
    //                 ]
    //             );
    //         }

    //         throw new ServiceException(AppError::MESH_REGISTRY_ENDPOINT_INVITATION_SERVICE_PROVIDER_RESPONSE_INVALID);
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         // try to delete the previously inserted new provider
    //         $this->meshRegistryService->deleteInvitationServiceProvider($endpoint);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_ADD_PROVIDER_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     } catch (Exception $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         // final effort trying to delete the previously inserted new provider
    //         $this->meshRegistryService->deleteInvitationServiceProvider($endpoint);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_ADD_PROVIDER_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Validates the service provider response fields
    //  *
    //  * @param array $params
    //  * @return bool true if validated, false otherwise
    //  */
    // private function verifyInvitationServiceProviderResponse(array $params): bool
    // {
    //     if (
    //         is_array($params)
    //         && isset($params[Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT])
    //         && isset($params[Schema::INVITATION_SERVICE_PROVIDER_DOMAIN])
    //         && isset($params[Schema::INVITATION_SERVICE_PROVIDER_NAME])
    //     ) {
    //         return true;
    //     }
    //     $this->logger->error('Could not validate the response fields. Fields: ' . print_r($params, true), ['app' => Application::APP_ID]);
    //     return false;
    // }

    // /**
    //  * Deletes the invitation service provider with the specified endpoint.
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @param string $endpoint the endpoint of the invitation service provider to delete
    //  * @return DataResponse if successfull, echoes the endpoint of the deleted service provider
    //  */
    // public function deleteInvitationServiceProvider(string $endpoint): DataResponse
    // {
    //     try {
    //         $invitationServiceProvider = $this->meshRegistryService->deleteInvitationServiceProvider($endpoint);
    //         return new DataResponse(
    //             [
    //                 'data' => $endpoint
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_DELETE_PROVIDER_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Whether only sharing with invited users is allowed.
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @param bool $allow
    //  * @return DataResponse
    //  */
    // public function setAllowSharingWithInvitedUsersOnly(bool $allow): DataResponse
    // {
    //     try {
    //         $result = $this->meshRegistryService->setAllowSharingWithInvitedUsersOnly(boolval($allow));
    //         return new DataResponse(
    //             [
    //                 'data' => $result,
    //             ],
    //             Http::STATUS_OK
    //         );
    //     } catch (Exception $e) {
    //         $this->logger->error("Unable to set 'allow_sharing_with_invited_users_only' config param. " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_SET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR
    //             ],
    //             Http::STATUS_NOT_FOUND
    //         );
    //     }
    // }

    // /**
    //  * Returnes this instance's invitation service provider endpoint.
    //  *
    //  * @PublicPage
    //  * @NoCSRFRequired
    //  *
    //  * @return DataResponse
    //  */
    // public function getEndpoint(): DataResponse
    // {
    //     try {
    //         $endpoint = $this->meshRegistryService->getEndpoint();
    //         return new DataResponse(
    //             [
    //                 'data' => $endpoint,
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_GET_ENDPOINT_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Sets the endpoint of this invitation service provider
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @param string $endpoint
    //  * @return DataResponse
    //  */
    // public function setEndpoint(string $endpoint): DataResponse
    // {
    //     try {
    //         $endpoint = $this->meshRegistryService->setEndpoint($endpoint);
    //         return new DataResponse(
    //             [
    //                 'data' => $endpoint,
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_GET_ENDPOINT_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Returnes this instance's invitation service provider name.
    //  *
    //  * @PublicPage
    //  * @NoCSRFRequired
    //  *
    //  * @return DataResponse
    //  */
    // public function getName(): DataResponse
    // {
    //     try {
    //         $name = $this->meshRegistryService->getName();
    //         return new DataResponse(
    //             [
    //                 'data' => $name,
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_GET_NAME_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Sets this instance's invitation service provider name.
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @param string $name
    //  * @return DataResponse
    //  */
    // public function setName(string $name): DataResponse
    // {
    //     try {
    //         $name = $this->meshRegistryService->setName($name);
    //         return new DataResponse(
    //             [
    //                 'data' => $name,
    //             ],
    //             Http::STATUS_OK,
    //         );
    //     } catch (ServiceException $e) {
    //         $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_SET_NAME_ERROR,
    //             ],
    //             Http::STATUS_NOT_FOUND,
    //         );
    //     }
    // }

    // /**
    //  * Whether only sharing with invited users is allowed.
    //  *
    //  * @NoCSRFRequired
    //  *
    //  * @return DataResponse
    //  */
    // public function getAllowSharingWithInvitedUsersOnly(): DataResponse
    // {
    //     try {
    //         $result = $this->meshRegistryService->getAllowSharingWithInvitedUsersOnly();
    //         return new DataResponse(
    //             [
    //                 'data' => $result,
    //             ],
    //             Http::STATUS_OK
    //         );
    //     } catch (Exception $e) {
    //         $this->logger->error("Unable to get 'allow_sharing_with_invited_users_only' config param. " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
    //         return new DataResponse(
    //             [
    //                 'message' => AppError::MESH_REGISTRY_SET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR
    //             ],
    //             Http::STATUS_NOT_FOUND
    //         );
    //     }
    // }
}
