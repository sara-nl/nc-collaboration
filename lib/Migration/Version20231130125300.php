<?php

namespace OCA\Invitation\Migration;

use OCA\Invitation\Db\Schema;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

/**
 * Creates invitation_constants and inserts constants
 */
class Version20231130125300 extends SimpleMigrationStep
{

    /** @var IDBConnection */
    private $dbc;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(IDBConnection $dbc, LoggerInterface $logger)
    {
        $this->dbc = $dbc;
        $this->logger = $logger;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options)
    {
        $prefix = $options['tablePrefix'];

        /**
         * @var ISchemaWrapper $schema
         */
        $schema = $schemaClosure();
        $prefix = "oc_";

        print_r("Changing schema: create views " . Schema::VIEW_INVITATIONS . ", " . Schema::VIEW_REMOTEUSERS . "\n");

        $sql = $this->dbc->getDatabasePlatform()->getCreateViewSQL(
            Schema::VIEW_INVITATIONS,
            "
                select distinct 
                s.id, s.token, s.timestamp, s.status,
                s.user_cloud_id, s.user_provider_endpoint, s.sent_received,
                s.provider_endpoint, s.recipient_endpoint, 
                s.sender_cloud_id, s.sender_name, s.sender_email, 
                s.recipient_cloud_id, s.recipient_name, s.recipient_email,
                s.remote_user_cloud_id, s.remote_user_name, s.remote_user_email, s.remote_user_provider_endpoint as remote_user_provider_endpoint, COALESCE(isp.name, '') as remote_user_provider_name
                from (
                select 
                i.id as id, i.token as token, i.timestamp as timestamp, i.status as status, 
                i.sender_cloud_id as user_cloud_id, i.provider_endpoint as user_provider_endpoint, 'sent' as sent_received,
                i.provider_endpoint as provider_endpoint, i.recipient_endpoint as recipient_endpoint, 
                i.sender_cloud_id as sender_cloud_id, i.sender_name as sender_name, i.sender_email as sender_email, 
                i.recipient_cloud_id as recipient_cloud_id, i.recipient_name as recipient_name, i.recipient_email as recipient_email,
                i.recipient_cloud_id as remote_user_cloud_id, i.recipient_name as remote_user_name, i.recipient_email as remote_user_email, i.recipient_endpoint as remote_user_provider_endpoint
                from {$prefix}invitation_invitations i
                    union all
                select 
                ii.id as id, ii.token as token, ii.timestamp as timestamp, ii.status as status, 
                ii.recipient_cloud_id as user_cloud_id, ii.recipient_endpoint as user_provider_endpoint, 'received' as sent_received,
                ii.provider_endpoint as provider_endpoint, ii.recipient_endpoint as recipient_endpoint, 
                ii.sender_cloud_id as sender_cloud_id, ii.sender_name as sender_name, ii.sender_email as sender_email, 
                ii.recipient_cloud_id as recipient_cloud_id, ii.recipient_name as recipient_name, ii.recipient_email as recipient_email,
                ii.sender_cloud_id as remote_user_cloud_id, ii.sender_name as remote_user_name, ii.sender_email as remote_user_email, ii.provider_endpoint as remote_user_provider_endpoint
                from {$prefix}invitation_invitations ii
                ) s
                left join {$prefix}invitation_srv_providers as isp
                on isp.endpoint=s.remote_user_provider_endpoint
                join {$prefix}appconfig c
                on c.configvalue=s.user_provider_endpoint
                where c.appid='invitation' and c.configkey='endpoint'
                group by s.id
                "
        );
        $this->dbc->executeStatement($sql);

        $sql = $this->dbc->getDatabasePlatform()->getCreateViewSQL(
            Schema::VIEW_REMOTEUSERS,
            "
                select distinct 
                s.invitation_id, s.user_cloud_id, s.user_name, s.remote_user_cloud_id, s.remote_user_name, s.remote_user_email, s.remote_provider_endpoint as remote_user_provider_endpoint, isp.name as remote_user_provider_name
                from (
                select 
                    i.id as invitation_id, i.provider_endpoint as provider_endpoint, 
                    i.sender_cloud_id as user_cloud_id, i.sender_name as user_name, 
                    i.recipient_cloud_id as remote_user_cloud_id, i.recipient_name as remote_user_name, i.recipient_email as remote_user_email, i.recipient_endpoint as remote_provider_endpoint
                from {$prefix}invitation_invitations i
                    where i.status='accepted'
                union all
                select 
                    ii.id as invitation_id, ii.recipient_endpoint as provider_endpoint, 
                    ii.recipient_cloud_id as user_cloud_id, ii.recipient_name as user_name, 
                    ii.sender_cloud_id as remote_user_cloud_id, ii.sender_name as remote_user_name, ii.sender_email as remote_user_email, ii.provider_endpoint as remote_provider_endpoint
                from {$prefix}invitation_invitations ii
                    where ii.status='accepted'
                ) s
                join {$prefix}invitation_srv_providers as isp
                on isp.endpoint=s.remote_provider_endpoint
                join {$prefix}appconfig c
                on c.configvalue=s.provider_endpoint
                where c.appid='invitation' and c.configkey='endpoint'
                group by s.invitation_id
                "
        );
        $this->dbc->executeStatement($sql);

        return $schema;
    }
}
