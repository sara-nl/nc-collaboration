<?php

namespace OCA\Collaboration\Controller;

use OCA\Collaboration\AppInfo\AppError;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Service\CollaborationServiceProviderService;
use OCA\Collaboration\Service\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class CollaborationServiceProviderController extends Controller
{
    /** @var IAppConfig */
    private IAppConfig $config;
    /** @var IAppContainer */
    private IAppContainer $router;
    /** @var CollaborationServiceProviderService */
    private CollaborationServiceProviderService $collaborationServiceProviderService;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    public function __construct(
        IRequest $request,
        IAppConfig $config,
        IAppContainer $router,
        CollaborationServiceProviderService $collaborationServiceProviderService,
        LoggerInterface $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->config = $config;
        $this->router = $router;
        $this->collaborationServiceProviderService = $collaborationServiceProviderService;
        $this->logger = $logger;
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
}
