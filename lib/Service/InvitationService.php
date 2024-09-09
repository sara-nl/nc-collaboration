<?php

namespace OCA\Collaboration\Service;

use Exception;
use OCA\Collaboration\AppInfo\Application;
use OCA\Collaboration\Db\Schema;
use OCA\Collaboration\Federation\Invitation;
use OCA\Collaboration\Federation\InvitationMapper;
use OCA\Collaboration\Federation\VInvitation;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * The service between controller and persistancy layer:
 *  - invitation access rights of the current user are handled here
 */
class InvitationService
{
    private InvitationMapper $mapper;
    private IUserSession $userSession;
    private LoggerInterface $logger;

    public function __construct(InvitationMapper $mapper, IUserSession $userSession, LoggerInterface $logger)
    {
        $this->mapper = $mapper;
        $this->userSession = $userSession;
        $this->logger = $logger;
    }

    /**
     * Returns the invitation with the specified id.
     *
     * @param int $id
     * @return VInvitation
     * @throws NotFoundException in case the invitation could not be found
     */
    public function find(int $id): VInvitation
    {
        try {
            $invitation = $this->mapper->find($id);
            if ($this->userSession->getUser()->getCloudId() === $invitation->getUserCloudID()) {
                return $invitation;
            }
            $this->logger->debug("User with cloud id '" . $this->userSession->getUser()->getCloudId() . "' is not authorized to access invitation with id '$id'.", ['app' => Application::APP_ID]);
            throw new NotFoundException("Invitation with id=$id not found.");
        } catch (NotFoundException $e) {
            $this->logger->debug($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new NotFoundException("Invitation with id=$id not found.");
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new NotFoundException("Invitation with id=$id not found.");
        }
    }

    /**
     * Returns the invitation with the specified token.
     *
     * @param string $token
     * @param bool $protected true if we need session user access check, default is true
     * @return VInvitation
     * @throws NotFoundException in case the invitation could not be found
     * @throws ServiceException in case of error
     */
    public function getByToken(string $token, bool $protected = true): VInvitation
    {
        $invitation = null;
        try {
            $invitation = $this->mapper->getByToken($token);
        } catch (NotFoundException $e) {
            $this->logger->error("Invitation not found for token '$token'.", ['app' => Application::APP_ID]);
            throw new NotFoundException("An exception occurred trying to retrieve the invitation with token '$token'.");
        }
        if ($protected == true && $this->userSession->getUser() == null) {
            throw new ServiceException("Unable to find invitation, unauthenticated.");
        }
        if (
            $protected == false
            || $this->userSession->getUser()->getCloudId() === $invitation->getUserCloudID()
        ) {
            return $invitation;
        }
        throw new NotFoundException("An exception occurred trying to retrieve the invitation with token '$token'.");
    }

    /**
     * Returns all invitations matching the specified criteria.
     *
     * @param array $criteria
     * @return array
     * @throws ServiceException
     */
    public function findAll(array $criteria, bool $protected = true): array
    {
        try {
            // add access restriction
            if ($protected) {
                $criteria[Schema::VINVITATION_USER_CLOUD_ID] = [$this->userSession->getUser()->getCloudId()];
            }
            return $this->mapper->findAll($criteria);
        } catch (Exception $e) {
            $this->logger->error('findAll failed with error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new ServiceException('Failed to find all invitations for the specified criteria.');
        }
    }

    /**
     * Inserts the specified invitation.
     *
     * @param Invitation $invitation
     * @return Invitation
     * @throws ServiceException
     */
    public function insert(Invitation $invitation): Invitation
    {
        try {
            return $this->mapper->insert($invitation);
        } catch (Exception $e) {
            $this->logger->error('Message: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), ['app' => Application::APP_ID]);
            throw new ServiceException('Error inserting the invitation.');
        }
    }

    /**
     * Updates the invitation according to the specified fields and values.
     *
     * @param array $fieldsAndValues one of which must be the token
     * @param bool $protected true if we need session user access check, default is true
     * @return bool true if update succeeded, otherwise false
     */
    public function update(array $fieldsAndValues, bool $protected = true): bool
    {
        if ($protected === true) {
            if ($this->userSession->getUser() == null) {
                $this->logger->debug('Unable to update invitation, unauthenticated.', ['app' => Application::APP_ID]);
                return false;
            }
            return $this->mapper->updateInvitation($fieldsAndValues, $this->userSession->getUser()->getCloudId());
        } else {
            return $this->mapper->updateInvitation($fieldsAndValues);
        }
    }

    /**
     * Delete all invitations that have one of the specified statuses.
     *
     * @param array $statusses
     * @return void
     * @throws ServiceException
     */
    public function deleteForStatus(array $statuses): void
    {
        try {
            $this->mapper->deleteForStatus($statuses);
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage());
        }
    }
}
