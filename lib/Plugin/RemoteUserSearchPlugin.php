<?php

namespace OCA\Collaboration\Plugin;

use OCA\Collaboration\AppInfo\Application;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\Federation\ICloudIdManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class RemoteUserSearchPlugin implements ISearchPlugin
{

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var array */
    protected array $pluginList = [];

    public function __construct(
		private ICloudIdManager $cloudIdManager,
        LoggerInterface $logger,)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $search
     * @param int $limit
     * @param int $offset
     * @param ISearchResult $searchResult
     * @return bool whether the plugin has more results
     * @since 13.0.0
     */
    public function search($search, $limit, $offset, ISearchResult $searchResult): bool
    {
        $this->logger->debug(' - searching for remote users ... (returning Tom)', ['app' => Application::APP_ID]);

        $result = ['wide' => [], 'exact' => []];
        $resultType = new SearchResultType('remotes');

        [$remoteUser, $serverUrl] = $this->splitUserRemote('tom@nc-2.nl');

        // TODO find out what is the difference between 'exact' and 'wide'
        $result['exact'][] = [
            'label' => 'Tom Janssen (tom@nc-2.nl)',
            // 'uuid' => 'dan',
            'name' => 'Tom Janssen (collaboration address book)',
            // 'type' => $cloudIdType,
            'value' => [
                'shareType' => IShare::TYPE_REMOTE,
                'shareWith' => "tom@nc-2.nl",
                'server' => $serverUrl,
            ],
        ];

		$searchResult->addResultSet($resultType, $result['wide'], $result['exact']);

        return true;
    }

    /**
     * split user and remote from federated cloud id
     *
     * @param string $address federated share address
     * @return array [user, remoteURL]
     * @throws \InvalidArgumentException
     */
    public function splitUserRemote(string $address): array
    {
        try {
            $cloudId = $this->cloudIdManager->resolveCloudId($address);
            return [$cloudId->getUser(), $cloudId->getRemote()];
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Invalid Federated Cloud ID', 0, $e);
        }
    }
}
