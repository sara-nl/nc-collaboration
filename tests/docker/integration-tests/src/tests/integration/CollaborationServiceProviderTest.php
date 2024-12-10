<?php

namespace tests\integration;

use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use tests\util\HttpClient;
use tests\util\Util;

class CollaborationServiceProviderTest extends TestCase
{
    private const NC_1_HOST = "http://nc-1.nl";
    private const NC_1_APP_BASEURL = "https://nc-1.nl/apps/collaboration";
    private const NC_2_HOST = "https://nc-2.nl";

    private string $nc_1AuthToken = "";
    private string $nc_2AuthToken = "";

    private string $nc1Domain = "";
    private string $nc1ProviderUuid = "";
    private string $nc2Domain = "";
    private string $nc2ProviderUuid = "";

    private const PARAM_NAME_EMAIL = "email";
    private const PARAM_NAME_RECIPIENT_NAME = "recipientName";
    private const PARAM_NAME_SENDER_NAME = "senderName";
    private const PARAM_NAME_MESSAGE = "message";

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

        $this->nc1Domain = getenv('NC1_DOMAIN');
        $this->nc1ProviderUuid = getenv('NC1_PROVIDER_UUID');
        $this->nc2Domain = getenv('NC2_DOMAIN');
        $this->nc2ProviderUuid = getenv('NC2_PROVIDER_UUID');
    }

    public function testCollaborationServiceProviderProperties()
    {
        try {
            $endpoint = "/provider";
            print_r("\ntesting unprotected endpoint GET $endpoint\n");
            $url = self::NC_1_APP_BASEURL . $endpoint;
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet($url);
            print_r("\n" . print_r($response, true) . "\n");

            $this->assertEquals($this->nc1Domain, $response['data']['domain'], "Domain is not what is expected.");
            $this->assertEquals($this->nc1ProviderUuid, $response['data']['uuid'], "UUID is not what is expected.");
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    public function testCollaborationServiceProviderServices()
    {
        try {
            $endpoint = "/provider/services";
            print_r("\ntesting unprotected endpoint GET $endpoint\n");
            $url = self::NC_1_APP_BASEURL . $endpoint;
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet($url);
            print_r("\n" . print_r($response, true) . "\n");

            $this->assertEquals(2, count($response['data']), "Nr of services is not what is expected.");
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Test GET /provider/create-invite
     * @return array array containing: 
     * [
     *  'token' => $response['data']['token'],
     *  'senderEmail' => $response['data']['senderEmail'],
     *  'providerUuid' => $response['data']['providerUuid'],
     *  'providerDomain' => $response['data']['providerDomain'],
     *  'Location' => $response['headers']['Location'],
     *  'InviteLink' => $response['headers']['InviteLink'],
     * ]
     */
    public function testCreateInvite(): array
    {
        try {
            $endpoint = "/provider/create-invite";
            print_r("\ntesting protected endpoint POST $endpoint\n");
            $url = self::NC_1_APP_BASEURL . $endpoint;
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
     * Retrieve the created invitation by token.
     * 
     * @depends testCreateInvite
     * @return array
     */
    public function testCreateInviteResultToken(array $result): array
    {
        try {
            $token = $result['token'];
            $senderEmail = $result['senderEmail'];
            $endpoint = "/invitations/{$token}";
            $url = self::NC_1_APP_BASEURL . "{$endpoint}";
            print_r("\nTesting retrieval created invitation by token: {$endpoint}\n");
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
     * Retrieve the created invitation by the Location.
     * 
     * @depends testCreateInviteResultToken
     * @return array
     */
    public function testCreateInviteResultLocation(array $result): array
    {
        try {
            $url = $result['Location'];
            print_r("\nTesting retrieval created invitation by Location: $url\n");
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
     * Test the unprotected /mesh-registry/forward-invite endpoint using the received invite link.
     * The endpoint should return the WAYF page.
     * 
     * @depends testCreateInviteResultLocation
     * @return array
     */
    public function testCreateInviteResultInviteLink(array $result): array
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
            print_r("\nTesting created invitation InviteLink (/mesh-registry/forward-invite endpoint): $url\n");
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet(
                $url,
                "",
                "",
                "",
                ["Content-Type: text/html; charset=utf-8"],
            );
            print_r("\n" . print_r($response, true) . "\n");
            $this->assertEquals(200, $response['http_status_code'], '/forward-invite returned http response code ' . $response['http_status_code']);
            return $result;
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Test the unprotected /accept-invite endpoint to accept the invite.
     * 
     * @depends testCreateInviteResultInviteLink
     * @return void
     */
    public function testAcceptInvite(array $result): void
    {
        try {
            $token = $result['token'];
            $endpoint = "/provider/accept-invite/{$token}";
            $url = self::NC_1_APP_BASEURL . "{$endpoint}";
            print_r("\nTesting accept invite: {$endpoint}\n");
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
                $this->assertEquals($token, $response['data']['token'], "Returned token '" . $response['data']['token'] . "'\n");
                $this->assertEquals('accepted', $response['data']['status'], "Returned status '" . $response['data']['status'] . "'\n");
            } else if (isset($response['message'])) {
                $this->fail($response['message']);
            } else {
                $this->fail("No result returned");
            }
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
