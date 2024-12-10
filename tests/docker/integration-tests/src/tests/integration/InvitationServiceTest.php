<?php

namespace tests\integration;

use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use tests\util\HttpClient;
use tests\util\Util;

class InvitationsTest extends TestCase
{
    private const NC_1_HOST = "https://nc-1.nl";
    private const NC_1_APP_BASEURL = "https://nc-1.nl/apps/collaboration";

    private const NC_2_HOST = "https://nc-2.nl";

    private string $nc_1AuthToken = "";
    private string $nc_2AuthToken = "";

    private string $nc1ProviderUuid = "";
    private string $nc1Domain = "";
    private string $nc2ProviderUuid = "";
    private string $nc2Domain = "";

    public function setUp(): void
    {
        print_r("\nsetUp test\n");
        parent::setUp();
        if ($this->nc_1AuthToken == "") {
            $this->nc_1AuthToken = Util::getAuthToken(self::NC_1_HOST, 'admin', getenv('ADMIN_PASS'));
        }
        print_r("Received nc-1.nl auth token: {$this->nc_1AuthToken} \n");

        if ($this->nc_2AuthToken == "") {
            $this->nc_2AuthToken = Util::getAuthToken(self::NC_2_HOST, 'admin', getenv('ADMIN_PASS'));
        }
        print_r("Received nc-2.nl auth token: {$this->nc_2AuthToken} \n");

        $this->nc1ProviderUuid = getenv('NC1_PROVIDER_UUID');
        $this->nc1Domain = getenv('NC1_DOMAIN');
        $this->nc2ProviderUuid = getenv('NC2_PROVIDER_UUID');
        $this->nc2Domain = getenv('NC2_DOMAIN');
    }

    /**
     * Find the test invitation with status invalid
     * 
     * @return array the data, ie, the invitation as associated array
     */
    public function testFindInvitations(): array
    {
        try {
            $endpoint = "/invitations";
            print_r("\ntesting protected endpoint GET $endpoint\n");
            $url = self::NC_1_APP_BASEURL . $endpoint . "?status=invalid";
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet(
                $url,
                "",
                $this->nc_1AuthToken,
            );
            print_r("\nresponse: " . print_r($response, true) . "\n");
            if (isset($response['data'])) {
                $this->assertIsArray($response['data'][0], "Result is  not an array.");
                $this->assertEquals('invalid', $response['data'][0]['status'], "Status is not what is expected.");
            } else if (isset($response['message'])) {
                $this->fail($response['message']);
            } else {
                $this->fail("No result returned");
            }
            return $response['data'][0];
        } catch (Exception $e) {
            print_r($e->getMessage() . '\n');
            print_r($e->getTraceAsString() . '\n');
        }
    }

    /**
     * Test posting a new invitation
     * 
     */
    public function testNewInvitation(): array
    {
        try {
            $endpoint = "/invitations";
            print_r("\ntesting protected endpoint POST $endpoint\n");
            $url = self::NC_1_APP_BASEURL . $endpoint;
            $httpClient = new HttpClient();

            print_r("\ntest POST $endpoint\n");
            $token = Uuid::uuid4()->toString();
            $response = $httpClient->curlPost(
                $url,
                [
                    'params' => [
                        'token' => $token,
                        'status' => 'open',
                        'providerUuid' => $this->nc1ProviderUuid,
                        'providerDomain' => $this->nc1Domain,
                        'recipientProviderUuid' => $this->nc2ProviderUuid,
                        'recipientProviderDomain' => $this->nc2Domain,
                        'senderCloudId' => 'admin@nc-1.nl',
                        'senderEmail' => 'admin@nc-1.nl',
                        'senderName' => 'Ad Min',
                        'recipientCloudId' => 'admin@nc-2.nl',
                        'recipientEmail' => 'admin@nc-2.nl',
                        'recipientName' => 'Ad Plus',
                        'timestamp' => time(),
                    
                    ]
                ],
                "",
                $this->nc_1AuthToken,
            );
            print_r("\n" . print_r($response, true));
            $this->assertEquals(201, $response['http_status_code'], "New invitation returned http response code: " . $response['http_status_code']);
            $this->assertEquals($token, $response['data']['token'], "Update invitation returned unexpected status: " . $response['data']['token']);
            return $response['data'];
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Updates an invitation
     * 
     * @depends testNewInvitation
     * 
     */
    public function testUpdateInvitation(array $invitation): void
    {
        try {
            $token = $invitation['token'];
            $endpoint = "/invitations/$token";
            print_r("\ntesting protected endpoint PATCH $endpoint\n");
            $url = self::NC_1_APP_BASEURL . $endpoint;
            $httpClient = new HttpClient();

            $currentStatus = $invitation['status'];
            print_r("\ntest PATCH $endpoint > update status '$currentStatus' to 'withdrawn'\n");
            $response = $httpClient->curlPatch(
                $url,
                [
                    'params' => [
                        'token' => $token,
                        'status' => 'withdrawn'
                    ]
                ],
                "",
                $this->nc_1AuthToken,
            );
            print_r("\n" . print_r($response, true));
            $this->assertEquals(200, $response['http_status_code'], "Update invitation returned http response code: " . $response['http_status_code']);
            $this->assertEquals('withdrawn', $response['data']['status'], "Update invitation returned unexpected status: " . $response['data']['status']);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    // /**
    //  * Tests route PUT /invitations/{token}
    //  * Updates the status of sent invitation to revoked
    //  * 
    //  * @return string $status the status that the invitation has been updated to
    //  */
    // public function testRevokeInvitation()
    // {
    //     $token = $this->tokenAcceptedInvitation;
    //     $endpoint = "/invitations/$token";
    //     print_r("\ntesting protected endpoint PUT $endpoint\n");
    //     $url = self::NC_1_APP_OCS_BASEURL . "$endpoint";
    //     $httpClient = new HttpClient();

    //     $basicAuthToken = base64_encode("admin:{$this->authToken}");

    //     $newStatus = "revoked";
    //     $response = $httpClient->curlPut(
    //         $url,
    //         ["status" => $newStatus],
    //         $basicAuthToken,
    //         "",
    //         ["OCS-APIRequest: true"]
    //     );
    //     print_r("\n" . print_r($response, true) . "\n");

    //     $this->assertEquals($token, $response['data']['token'], "Updating the invitation status failed.");
    //     $this->assertEquals($newStatus, $response['data']['status'], "Updating the invitation status failed.");
    //     return $newStatus;
    // }

    // /**
    //  * Tests route PUT /invitations/{token}
    //  * Accepts the invitation
    //  * 
    //  * @return string $status the status that the invitation has been updated to
    //  */
    // public function xxxAcceptInvitation()
    // {
    //     // Needs the instance from which the invitation came to respond to the /ocm/invite-accepted call.
    //     // We therefor currently test POST /ocm/invite-accepted endpoint separately in testOcmInviteAccepted().
    // }

    // /**
    //  * Test ocm POST /ocm/invite-accepted endpoint
    //  * User at nc-2 accepts invitation from user at nc-1
    //  */
    // public function testOcmInviteAccepted()
    // {
    //     $endpoint = "/ocm/invite-accepted";
    //     print_r("\ntesting protected endpoint POST $endpoint\n");
    //     $url = self::NC_1_APP_BASEURL . $endpoint;
    //     $httpClient = new HttpClient();

    //     print_r("\ntest unprotected POST $endpoint\n");
    //     $response = $httpClient->curlPost(
    //         $url,
    //         [
    //             "recipientProvider" => self::NC_2_INVITATION_SERVICE_PROVIDER_ENDPOINT,
    //             "token" => $this->tokenOpenSentInvitation, // sent by nc-1
    //             "userID" => "me@nc-2.nl@mesh.org",
    //             "email" => "me@nc-2.nl",
    //             "name" => "M.E",
    //         ]
    //     );
    //     print_r("\n" . print_r($response, true));
    //     assertEquals(1, 1, "Strange");
    // }

    public function tearDown(): void
    {
        print_r("\ntearDown test\n");
        parent::tearDown();
    }
}
