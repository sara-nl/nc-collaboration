<?php

namespace OCA\Collaboration\Service;

use Exception;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Db\CollaborationServiceProvider;
use OCA\Collaboration\Db\CollaborationServiceProviderMapper;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Service\MeshRegistry\MeshRegistryService;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * The service between controller and persistancy layer:
 */
class CollaborationServiceProviderService
{
    /** @var CollaborationServiceProviderMapper */
    private CollaborationServiceProviderMapper $mapper;
    /** @var IAppConfig */
    private IAppConfig $appConfig;
    /** @var IUserSession */
    private IUserSession $userSession;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    public function __construct(
        CollaborationServiceProviderMapper $mapper,
        IAppConfig $appConfig,
        IUserSession $userSession,
        LoggerInterface $logger
    ) {
        $this->mapper = $mapper;
        $this->appConfig = $appConfig;
        $this->userSession = $userSession;
        $this->logger = $logger;
    }

    /**
     * Returns the Collaboration Service Provider with the specified uuid.
     *
     * @param string $uuid
     * @return CollaborationServiceProvider
     * @throws NotFoundException in case the Collaboration Service Provider could not be found
     * @throws ServiceException in case of error
     */
    public function getProviderByUuid(string $uuid): CollaborationServiceProvider
    {
        $collaborationServiceProvider = null;
        try {
            $collaborationServiceProvider = $this->mapper->getByUuid($uuid);
            return $collaborationServiceProvider;
        } catch (NotFoundException $e) {
            $this->logger->error("Provider not found for uuid '$uuid'. " . "Stacktrace: " . print_r($e->getTraceAsString(), true), ['app' => Application::APP_ID]);
            throw new NotFoundException("An exception occurred trying to retrieve the Collaboration Service Provider with uuid '$uuid'.");
        }
    }

    /**
     * Returns all providers matching the specified criteria.
     *
     * @param array $criteria
     * @return array the CollaborationServiceProvider entities
     * @throws ServiceException
     */
    public function findAll(array $criteria, bool $protected = true): array
    {
        try {
            // add access restriction
            if ($protected) {
                $criteria[Schema::INVITATION_USER_ID] = [$this->userSession->getUser()->getUID()];
            }
            return $this->mapper->findAll($criteria);
        } catch (Exception $e) {
            $this->logger->error('findAll failed with error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new ServiceException('Failed to find all providers for the specified criteria.');
        }
    }

    /**
     * Returns all services of this provider as array.
     *
     * @return array
     */
    public function getServices(): array
    {
        $services = [
            [
                'name' => InvitationService::NAME
            ],
            [
                'name' => MeshRegistryService::NAME
            ]
        ];
        return $services;
    }

    /**
     * Returns the uuid of this provider
     *
     * @return string the uuid of this provider
     */
    public function getUuid(): string
    {
        return $this->appConfig->getValueString(Application::APP_ID, Application::CONFIG_KEY_PROVIDER_UUID);
    }

    /**
     * Inserts the specified CollaborationServiceProvider object.
     *
     * @param CollaborationServiceProvider $collaborationServiceProvider
     * @return CollaborationServiceProvider
     * @throws ServiceException
     */
    public function insert(CollaborationServiceProvider $collaborationServiceProvider): CollaborationServiceProvider
    {
        try {
            return $this->mapper->insert($collaborationServiceProvider);
        } catch (Exception $e) {
            $this->logger->error('Message: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new ServiceException('Error inserting the provider.');
        }
    }
}
