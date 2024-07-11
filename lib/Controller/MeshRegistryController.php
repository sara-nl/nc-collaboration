<?php

/**
 * This is the mesh registry controller.
 *
 */

namespace OCA\Invitation\Controller;

use Exception;
use OCA\Invitation\AppInfo\AppError;
use OCA\Invitation\AppInfo\Application;
use OCA\Invitation\Db\Schema;
use OCA\Invitation\Federation\InvitationServiceProvider;
use OCA\Invitation\HttpClient;
use OCA\Invitation\Service\ApplicationConfigurationException;
use OCA\Invitation\Service\MeshRegistry\MeshRegistryService;
use OCA\Invitation\Service\NotFoundException;
use OCA\Invitation\Service\ServiceException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class MeshRegistryController extends Controller
{
    private MeshRegistryService $meshRegistryService;
    private LoggerInterface $logger;


    public function __construct(
        IRequest $request,
        MeshRegistryService $meshRegistryService,
        LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->meshRegistryService = $meshRegistryService;
        $this->logger = $logger;
    }

    /**
     * Provides the caller with a list (WAYF page) of mesh EFSS invitation service providers to choose from.

     * @NoCSRFRequired
     * @PublicPage
     *
     * @param string $token the token
     * @param string $providerEndpoint the endpoint of the sender
     * @param string $name the name of the sender
     * @return Response
     */
    public function forwardInvite(string $token = '', string $providerEndpoint = ''): Response
    {
        $urlGenerator = \OC::$server->getURLGenerator();

        if ($token == '') {
            $this->logger->error('Invite is missing the token.', ['app' => Application::APP_ID]);
            return new RedirectResponse(
                $urlGenerator->linkToRoute(
                    Application::APP_ID . '.error.invitation',
                    [
                        'message' => AppError::HANDLE_INVITATION_MISSING_TOKEN
                    ]
                )
            );
        }
        if ($providerEndpoint == '') {
            $this->logger->error('Invite is missing the invitation service provider endpoint.', ['app' => Application::APP_ID]);
            return new RedirectResponse(
                $urlGenerator->linkToRoute(
                    Application::APP_ID . '.error.invitation',
                    [
                        'message' => AppError::HANDLE_INVITATION_MISSING_PROVIDER_ENDPOINT
                    ]
                )
            );
        }
        if (!$this->meshRegistryService->isKnowInvitationServiceProvider($providerEndpoint)) {
            $this->logger->error("Invitation service provider endpoint '$providerEndpoint' is unknown.", ['app' => Application::APP_ID]);
            return new RedirectResponse(
                $urlGenerator->linkToRoute(
                    Application::APP_ID . '.error.invitation',
                    [
                        'message' => AppError::HANDLE_INVITATION_PROVIDER_UNKNOWN
                    ]
                )
            );
        }

        $urlGenerator = \OC::$server->getURLGenerator();
        $params = [
            MeshRegistryService::PARAM_NAME_TOKEN => $token,
            MeshRegistryService::PARAM_NAME_PROVIDER_ENDPOINT => $providerEndpoint,
        ];
        return new RedirectResponse(
            $urlGenerator->linkToRoute($this->meshRegistryService->getWayfPageRoute(), $params)
        );
    }

    /**
     * Returns the properties of the this invitation service provider.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return DataResponse ['data' => :InvitationServiceProvider]
     */
    public function invitationServiceProvider(): DataResponse
    {
        try {
            return new DataResponse(
                [
                    'data' => $this->meshRegistryService->getInvitationServiceProvider()->jsonSerialize(),
                ],
                Http::STATUS_OK,
            );
        } catch (NotFoundException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_GET_PROVIDER_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Updates this instance's invitation service provider properties.
     *
     * @NoCSRFRequired
     *
     * @param string $endpoint
     * @param string $name
     * @return DataResponse
     */
    public function updateInvitationServiceProvider(string $endpoint, string $name): DataResponse
    {
        try {
            $fieldsArray = ['endpoint' => $endpoint, 'name' => $name];

            $endpoint = "";
            try {
                $endpoint = $this->meshRegistryService->getEndpoint();
            } catch (ApplicationConfigurationException $e) {
                // no endpoint yet, this is the initialization of this instances provider
            }

            // check the endpoint connection
            $url = $this->meshRegistryService->getFullInvitationServiceProviderEndpointUrl($fieldsArray['endpoint']);
            $httpClient = new HttpClient($this->logger);
            $response = $httpClient->curlGet($url);
            if ($response['success'] == false) {
                $this->logger->error('Failed to call ' . MeshRegistryService::ENDPOINT_INVITATION_SERVICE_PROVIDER . " on endpoint '$endpoint'. Response: " . print_r($response, true), ['app' => Application::APP_ID]);
                throw new ServiceException("Failed to call endpoint '$endpoint'");
            }

            $isp = $this->meshRegistryService->updateInvitationServiceProvider($endpoint, $fieldsArray);

            return new DataResponse(
                [
                    'data' => $isp->jsonSerialize(),
                ],
                Http::STATUS_OK,
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . " Trace: " . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_SET_ENDPOINT_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Returns all registered invitation service providers.
     *
     * @NoCSRFRequired
     * @PublicPage
     * @return DataResponse ['data' => [:InvitationServiceProvider](an array of InvitationServiceProvider objects)]
     */
    public function invitationServiceProviders(): DataResponse
    {
        try {
            $providers = $this->meshRegistryService->allInvitationServiceProviders();
            return new DataResponse(
                [
                    'data' => $providers,
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_ALL_PROVIDERS_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Adds a new invitation service provider with the specified endpoint.
     * The properties of the provider will be requested through the specified endpoint.
     * If this fails an HTTP error will be returned.
     *
     * Note: if the provider already exists it's properties will become updated
     * through the remote provider /registry/invitation-service-provider call.
     *
     * @NoCSRFRequired
     *
     * @param string $endpoint the endpoint of the new invitation service provider
     * @return DataResponse [ ..., 'data' => :InvitationServiceProvider ]
     */
    public function addInvitationServiceProvider(string $endpoint): DataResponse
    {
        try {
            // some sanitizing
            $endpoint = trim(trim($endpoint), '/');

            if ($endpoint === $this->meshRegistryService->getEndpoint()) {
                return new DataResponse(
                    [
                        'message' => AppError::SETTINGS_ADD_PROVIDER_IS_NOT_REMOTE_ERROR,
                    ],
                    Http::STATUS_NOT_FOUND,
                );
            }

            // check whether the provider is not already registered
            try {
                $provider = $this->meshRegistryService->findInvitationServiceProvider($endpoint);
                $this->logger->error("The provider with endpoint $endpoint is already registered", ['app' => Application::APP_ID]);
                return new DataResponse(
                    [
                        'message' => AppError::MESH_REGISTRY_ADD_PROVIDER_EXISTS_ERROR,
                    ],
                    Http::STATUS_NOT_FOUND,
                );
            } catch (NotFoundException $e) {
                // all good
            }

            $url = $this->meshRegistryService->getFullInvitationServiceProviderEndpointUrl($endpoint);
            $httpClient = new HttpClient($this->logger);
            $response = $httpClient->curlGet($url);
            if ($response['success'] == false) {
                $this->logger->error('Failed to call ' . MeshRegistryService::ENDPOINT_INVITATION_SERVICE_PROVIDER . " on endpoint '$endpoint'. Response: " . print_r($response, true), ['app' => Application::APP_ID]);
                throw new ServiceException("Failed to call endpoint '$endpoint'");
            }

            $data = (array)$response['data'];
            $verified = $this->verifyInvitationServiceProviderResponse($data);
            $this->logger->debug(print_r($data, true));
            if ($verified === true) {
                $invitationServiceProvider = new InvitationServiceProvider();
                $invitationServiceProvider->setEndpoint($data[Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT]);
                $invitationServiceProvider->setDomain($data[Schema::INVITATION_SERVICE_PROVIDER_DOMAIN]);
                $invitationServiceProvider->setName($data[Schema::INVITATION_SERVICE_PROVIDER_NAME]);

                $invitationServiceProvider = $this->meshRegistryService->addInvitationServiceProvider($invitationServiceProvider);

                return new DataResponse(
                    [
                        'data' => $invitationServiceProvider->jsonSerialize(),
                    ]
                );
            }

            throw new ServiceException(AppError::MESH_REGISTRY_ENDPOINT_INVITATION_SERVICE_PROVIDER_RESPONSE_INVALID);
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            // try to delete the previously inserted new provider
            $this->meshRegistryService->deleteInvitationServiceProvider($endpoint);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_ADD_PROVIDER_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            // final effort trying to delete the previously inserted new provider
            $this->meshRegistryService->deleteInvitationServiceProvider($endpoint);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_ADD_PROVIDER_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Validates the service provider response fields
     *
     * @param array $params
     * @return bool true if validated, false otherwise
     */
    private function verifyInvitationServiceProviderResponse(array $params): bool
    {
        if (
            is_array($params)
            && isset($params[Schema::INVITATION_SERVICE_PROVIDER_ENDPOINT])
            && isset($params[Schema::INVITATION_SERVICE_PROVIDER_DOMAIN])
            && isset($params[Schema::INVITATION_SERVICE_PROVIDER_NAME])
        ) {
            return true;
        }
        $this->logger->error('Could not validate the response fields. Fields: ' . print_r($params, true), ['app' => Application::APP_ID]);
        return false;
    }

    /**
     * Deletes the invitation service provider with the specified endpoint.
     *
     * @NoCSRFRequired
     *
     * @param string $endpoint the endpoint of the invitation service provider to delete
     * @return DataResponse if successfull, echoes the endpoint of the deleted service provider
     */
    public function deleteInvitationServiceProvider(string $endpoint): DataResponse
    {
        try {
            $invitationServiceProvider = $this->meshRegistryService->deleteInvitationServiceProvider($endpoint);
            return new DataResponse(
                [
                    'data' => $endpoint
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_DELETE_PROVIDER_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Whether only sharing with invited users is allowed.
     *
     * @NoCSRFRequired
     *
     * @param bool $allow
     * @return DataResponse
     */
    public function setAllowSharingWithInvitedUsersOnly(bool $allow): DataResponse
    {
        try {
            $result = $this->meshRegistryService->setAllowSharingWithInvitedUsersOnly(boolval($allow));
            return new DataResponse(
                [
                    'data' => $result,
                ],
                Http::STATUS_OK
            );
        } catch (Exception $e) {
            $this->logger->error("Unable to set 'allow_sharing_with_invited_users_only' config param. " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_SET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR
                ],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * Returnes this instance's invitation service provider endpoint.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getEndpoint(): DataResponse
    {
        try {
            $endpoint = $this->meshRegistryService->getEndpoint();
            return new DataResponse(
                [
                    'data' => $endpoint,
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_GET_ENDPOINT_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Sets the endpoint of this invitation service provider
     *
     * @NoCSRFRequired
     *
     * @param string $endpoint
     * @return DataResponse
     */
    public function setEndpoint(string $endpoint): DataResponse
    {
        try {
            $endpoint = $this->meshRegistryService->setEndpoint($endpoint);
            return new DataResponse(
                [
                    'data' => $endpoint,
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_GET_ENDPOINT_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Returnes this instance's invitation service provider name.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getName(): DataResponse
    {
        try {
            $name = $this->meshRegistryService->getName();
            return new DataResponse(
                [
                    'data' => $name,
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_GET_NAME_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Sets this instance's invitation service provider name.
     *
     * @NoCSRFRequired
     *
     * @param string $name
     * @return DataResponse
     */
    public function setName(string $name): DataResponse
    {
        try {
            $name = $this->meshRegistryService->setName($name);
            return new DataResponse(
                [
                    'data' => $name,
                ],
                Http::STATUS_OK,
            );
        } catch (ServiceException $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_SET_NAME_ERROR,
                ],
                Http::STATUS_NOT_FOUND,
            );
        }
    }

    /**
     * Whether only sharing with invited users is allowed.
     *
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getAllowSharingWithInvitedUsersOnly(): DataResponse
    {
        try {
            $result = $this->meshRegistryService->getAllowSharingWithInvitedUsersOnly();
            return new DataResponse(
                [
                    'data' => $result,
                ],
                Http::STATUS_OK
            );
        } catch (Exception $e) {
            $this->logger->error("Unable to get 'allow_sharing_with_invited_users_only' config param. " . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            return new DataResponse(
                [
                    'message' => AppError::MESH_REGISTRY_SET_ALLOW_SHARING_WITH_INVITED_USERS_ONLY_ERROR
                ],
                Http::STATUS_NOT_FOUND
            );
        }
    }
}
