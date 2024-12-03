<?php

namespace tests\integration;

use Exception;
use OCA\Collaboration\Db\Invitation;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use tests\util\AppError;
use tests\util\HttpClient;
use tests\util\Util;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

class InvitationsTest extends TestCase
{
    private const NC_1_HOST = "https://nc-1.nl";
    private const NC_1_APP_BASEURL = "https://nc-1.nl/apps/collaboration";
    private const NC_1_APP_OCS_BASEURL = "https://nc-1.nl/ocs/v2.php/collaboration";

    private const NC_2_HOST = "https://nc-2.nl";
    private const NC_2_APP_BASEURL = "https://nc-2.nl/apps/collaboration";
    private const NC_2_APP_OCS_BASEURL = "https://nc-2.nl/ocs/v2.php/collaboration";

    private const PARAM_NAME_EMAIL = "email";
    private const PARAM_NAME_RECIPIENT_NAME = "recipientName";
    private const PARAM_NAME_SENDER_NAME = "senderName";
    private const PARAM_NAME_MESSAGE = "message";

    private string $nc_1AuthToken = "";
    private string $nc_2AuthToken = "";

    private $tokenAcceptedInvitation = "";
    private $tokenOpenSentInvitation = "";
    private $tokenOpenReceivedInvitation = "";


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

        $this->tokenAcceptedInvitation = getenv("TOKEN_ACCEPTED_INVITATION");
        $this->tokenOpenSentInvitation = getenv("TOKEN_OPEN_SENT_INVITATION");
        $this->tokenOpenReceivedInvitation = getenv("TOKEN_OPEN_RECEIVED_INVITATION");
    }

    /**
     * Find a the test invitation with status invalid
     */
    public function testFindInvitations()
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
                "",
                ["OCS-APIRequest: true"]
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
        } catch (Exception $e) {
            print_r($e->getMessage() . '\n');
            print_r($e->getTraceAsString() . '\n');
        }
    }

    /**
     * @return array an array with the token and sender email of the invitation: 
     * [
     *      'token' => token,
     *      'senderEmail' => senderEmail
     * ]
     */
    public function testCreateInvitation(): array
    {
        try {
            $endpoint = "/invitations";
            print_r("\ntesting protected endpoint POST $endpoint\n");
            $url = self::NC_1_APP_OCS_BASEURL . $endpoint;
            $httpClient = new HttpClient();

            // test no email specified
            print_r("\ntest POST $endpoint > error: no email specified\n");
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => "Me",
                    self::PARAM_NAME_MESSAGE => "message"
                ],
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));

            $this->assertEquals("CREATE_INVITATION_NO_RECIPIENT_EMAIL", $response['message'], 'No email address check failed.');

            // test email invalid
            print_r("\ntest POST $endpoint > email invalid\n");
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "invalid-email-address",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => "Me"
                ],
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));

            $this->assertEquals('CREATE_INVITATION_EMAIL_INVALID', $response['message'], 'Invalid email address response failure.');

            // test missing recipient name
            print_r("\ntest POST $endpoint > missing recipient name\n");
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "me@nc-1.nl",
                    self::PARAM_NAME_RECIPIENT_NAME => "",
                    self::PARAM_NAME_SENDER_NAME => "Me"
                ],
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));

            $this->assertEquals('CREATE_INVITATION_NO_RECIPIENT_NAME', $response['message'], 'Missing recipient name response failure.');

            // test missing recipient name
            print_r("\ntest POST $endpoint > missing sender name\n");
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "me@nc-1.nl",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => ""
                ],
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));

            $this->assertEquals('CREATE_INVITATION_NO_SENDER_NAME', $response['message'], 'Missing sender name response failure.');

            print_r("\ntest POST $endpoint\n");
            $message = urlencode('I want to invite you.');
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "me@nc-1.nl",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => "Me",
                    self::PARAM_NAME_MESSAGE => $message
                ],
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));
            $this->assertTrue(Uuid::isValid($response['data']['token']), 'POST $endpoint failed, invalid token returned.');
            $this->assertTrue(isset($response['headers']['Location']), 'POST $endpoint failed, no location header returned.');
            $this->assertTrue(isset($response['headers']['InviteLink']), 'POST $endpoint failed, no invite link header returned.');

            return [
                'token' => $response['data']['token'],
                'senderEmail' => $response['data']['senderEmail'],
                'providerUuid' => $response['data']['providerUuid'],
                'providerDomain' => $response['data']['providerDomain'],
                'Location' => $response['headers']['Location'],
                'InviteLink' => $response['headers']['InviteLink'],
            ];
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Retrieve the created invitation.
     * 
     * @depends testCreateInvitation
     * @return array
     */
    public function testGetInvitation(array $result): array
    {
        try {
            $token = $result['token'];
            $senderEmail = $result['senderEmail'];
            $endpoint = "/invitations/{$token}";
            $url = self::NC_1_APP_OCS_BASEURL . "{$endpoint}";
            print_r("\nTesting protected endpoint: {$endpoint}\n");
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet(
                $url,
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true) . "\n");
            if (isset($response['data'])) {
                $this->assertEquals($token, $result['token'], "GET {$url} failed\n");
                $this->assertEquals($senderEmail, $result['senderEmail'], "GET {$url} failed\n");
            } else if (isset($response['message'])) {
                $this->fail($response['message']);
            } else {
                $this->fail("No result returned");
            }
            return $result;
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Retrieve the created invitation by the location.
     * 
     * @depends testGetInvitation
     * @return array
     */
    public function testGetInvitationByLocation(array $result): array
    {
        try {
            $url = $result['Location'];
            print_r("\nTesting invitation location url: $url\n");
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet(
                $url,
                "",
                $this->nc_1AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true) . "\n");
            if (isset($response['data']['token'])) {
                $this->assertEquals($result['token'], $response['data']['token'], "Location does not seem to refer to the expected invitation\n");
            } else if (isset($response['message'])) {
                $this->fail($response['message']);
            } else {
                $this->fail("No result returned");
            }
            return $result;
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Test the unprotected /forward-invite endpoint using the received invite link.
     * The endpoint should return the WAYF page.
     * 
     * @depends testGetInvitationByLocation
     * @return void
     */
    public function testInviteLink(array $result): void
    {
        try {
            $inviteLink = $result['InviteLink'];
            // apparently we must reconstruct the invite link url string
            // looks like there are some troubelsome characters in it
            $urlAndParams = explode('?', $inviteLink);
            $allParams = explode('&', $urlAndParams[1]);
            $urlPart = $urlAndParams[0];
            $params = '';
            foreach ($allParams as $value) {
                $params .= "&$value";
            }
            $params = trim($params, '&');
            $url = "$urlPart?$params";
            print_r("\nTesting invitation link url: $url\n");
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet(
                $url,
                "",
                "",
                "",
                ["Content-Type: text/html; charset=utf-8"],
            );
            print_r("\n" . print_r($response, true) . "\n");
            $this->assertEquals(200, $response['http_status_code'], 'Invite link returned http response code ' . $response['http_status_code']);
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Test the unprotected /forward-invite endpoint using the received invite link.
     * The endpoint should return the WAYF page.
     * 
     * @depends testGetInvitationByLocation
     * @return void
     */
    public function testHandleInvite(array $result): void
    {
        try {
            $token = $result['token'];
            $providerUuid = $result['providerUuid'];
            $providerDomain = $result['providerDomain'];
            // $url =  self::NC_2_APP_OCS_BASEURL . "/invitation/handle-invite?token=$token&providerUuid=$providerUuid&providerDomain=$providerDomain";
            $url =  self::NC_2_APP_OCS_BASEURL . "/invitation/handle-invite";
            print_r("\nTest /handle-invite endpoint, missing token\n");
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet(
                $url,
                "",
                $this->nc_2AuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true) . "\n");
            $this->assertEquals(404, $response['http_status_code'], 'Handle invite returned http response code ' . $response['http_status_code']);
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    // public function testFindInvitations()
    // {
    //     try {
    //         $endpoint = "/invitation-service/invitations?status=accepted|open|withdrawn";
    //         print_r("\nTesting protected endpoint: {$endpoint}");
    //         $url = self::NC_1_APP_OCS_BASEURL . $endpoint;
    //         $httpClient = new HttpClient();
    //         $basicAuthToken = base64_encode("admin:{$this->authToken}");
    //         $response = $httpClient->curlGet($url, $basicAuthToken, "", ["OCS-APIRequest: true"]);
    //         print_r("\n" . print_r($response, true) . "\n");

    //         $this->assertEquals(3, count($response['data'][array_key_first($response['data'])]), "GET {$url} failed");
    //         return $response['data'];
    //     } catch (Exception $e) {
    //         $this->fail($e->getTraceAsString());
    //     }
    // }

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
