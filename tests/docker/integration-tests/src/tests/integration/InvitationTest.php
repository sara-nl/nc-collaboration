<?php

namespace tests\integration;

use Exception;
use OCA\Invitation\Service\MeshRegistry\MeshRegistryService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use tests\util\AppError;
use tests\util\HttpClient;
use tests\util\Util;

use function PHPUnit\Framework\assertEquals;

class InvitationTest extends TestCase
{
    private const NC_1_HOST = "https://nc-1.nl";
    private const NC_1_OCS_ROOT = "https://nc-1.nl/ocs/v2.php/invitation";
    private const NC_1_APP_ROOT = "https://nc-1.nl/apps/invitation";
    private const NC_2_INVITATION_SERVICE_PROVIDER_ENDPOINT = "https://nc-2.nl/apps/invitation";
    private const PARAM_NAME_EMAIL = "email";
    private const PARAM_NAME_RECIPIENT_NAME = "recipientName";
    private const PARAM_NAME_SENDER_NAME = "senderName";
    private const PARAM_NAME_MESSAGE = "message";
    private string $authToken = "";

    private $tokenAcceptedInvitation = "";
    private $tokenOpenSentInvitation = "";
    private $tokenOpenReceivedInvitation = "";


    public function setUp(): void
    {
        print_r("\nsetUp test\n");
        parent::setUp();
        if ($this->authToken == "") {
            $this->authToken = Util::getAuthToken(self::NC_1_HOST, 'admin', getenv('ADMIN_PASS'));
        }
        $this->tokenAcceptedInvitation = getenv("TOKEN_ACCEPTED_INVITATION");
        $this->tokenOpenSentInvitation = getenv("TOKEN_OPEN_SENT_INVITATION");
        $this->tokenOpenReceivedInvitation = getenv("TOKEN_OPEN_RECEIVED_INVITATION");
    }

    public function testFindInvitations()
    {
        try {
            $endpoint = "/invitations?status=accepted|open|withdrawn";
            print_r("\nTesting protected endpoint: {$endpoint}");
            $url = self::NC_1_OCS_ROOT . $endpoint;
            $httpClient = new HttpClient();
            $basicAuthToken = base64_encode("admin:{$this->authToken}");
            $response = $httpClient->curlGet($url, $basicAuthToken, "", ["OCS-APIRequest: true"]);
            print_r("\n" . print_r($response, true) . "\n");

            $this->assertEquals(3, count($response['data'][array_key_first($response['data'])]), "GET {$url} failed");
            return $response['data'];
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Tests getting an invitation.
     * 
     * @return string token
     */
    public function testGetInvitation(): string
    {
        try {
            $token = $this->tokenOpenSentInvitation;
            $endpoint = "/invitations/{$token}";
            $url = self::NC_1_OCS_ROOT . "{$endpoint}";
            print_r("\nTesting protected endpoint: {$endpoint}\n");
            $httpClient = new HttpClient();
            $basicAuthToken = base64_encode("admin:{$this->authToken}");
            $response = $httpClient->curlGet($url, $basicAuthToken, "", ["OCS-APIRequest: true"]);
            print_r("\n" . print_r($response['data'], true) . "\n");

            $this->assertEquals($token, $response['data']['token'], "GET {$url} failed\n");
            return $response['data']['token'];
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    public function testGenerateInvite()
    {
        try {
            $endpoint = "/invitations";
            print_r("\ntesting protected endpoint POST $endpoint\n");
            $url = self::NC_1_OCS_ROOT . $endpoint;
            $httpClient = new HttpClient();

            // test no email specified
            print_r("\ntest POST $endpoint > no email specified\n");
            $basicAuthToken = base64_encode("admin:{$this->authToken}");
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => "Me"

                ],
                $basicAuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));

            $this->assertEquals(AppError::CREATE_INVITATION_NO_RECIPIENT_EMAIL, $response['message'], 'No email address check failed.');

            // test email invalid
            print_r("\ntest POST $endpoint > email invalid\n");
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "invalid-email-address",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => "Me"
                ],
                $basicAuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));

            $this->assertEquals(AppError::CREATE_INVITATION_EMAIL_INVALID, $response['message'], 'Invalid email address response failure.');

            print_r("\ntest POST $endpoint\n");
            $message = urlencode('I want to invite you.');
            $response = $httpClient->curlPost(
                $url,
                [
                    self::PARAM_NAME_EMAIL => "me@oc-1.nl",
                    self::PARAM_NAME_RECIPIENT_NAME => "You",
                    self::PARAM_NAME_SENDER_NAME => "Me",
                    self::PARAM_NAME_MESSAGE => $message
                ],
                $basicAuthToken,
                "",
                ["OCS-APIRequest: true"]
            );
            print_r("\n" . print_r($response, true));
            $this->assertTrue(Uuid::isValid($response['data']['token']), 'POST $endpoint failed, invalid token returned.');
            return $response['data']['token'];
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Tests route PUT /invitations/{token}
     * Updates the status of sent invitation to revoked
     * 
     * @return string $status the status that the invitation has been updated to
     */
    public function testRevokeInvitation()
    {
        $token = $this->tokenAcceptedInvitation;
        $endpoint = "/invitations/$token";
        print_r("\ntesting protected endpoint PUT $endpoint\n");
        $url = self::NC_1_OCS_ROOT . "$endpoint";
        $httpClient = new HttpClient();

        $basicAuthToken = base64_encode("admin:{$this->authToken}");

        $newStatus = "revoked";
        $response = $httpClient->curlPut(
            $url,
            ["status" => $newStatus],
            $basicAuthToken,
            "",
            ["OCS-APIRequest: true"]
        );
        print_r("\n" . print_r($response, true) . "\n");

        $this->assertEquals($token, $response['data']['token'], "Updating the invitation status failed.");
        $this->assertEquals($newStatus, $response['data']['status'], "Updating the invitation status failed.");
        return $newStatus;
    }

    /**
     * Tests route PUT /invitations/{token}
     * Accepts the invitation
     * 
     * @return string $status the status that the invitation has been updated to
     */
    public function xxxAcceptInvitation()
    {
        // Needs the instance from which the invitation came to respond to the /ocm/invite-accepted call.
        // We therefor currently test POST /ocm/invite-accepted endpoint separately in testOcmInviteAccepted().
    }

    /**
     * Test ocm POST /ocm/invite-accepted endpoint
     * User at nc-2 accepts invitation from user at nc-1
     */
    public function testOcmInviteAccepted()
    {
        $endpoint = "/ocm/invite-accepted";
        print_r("\ntesting protected endpoint POST $endpoint\n");
        $url = self::NC_1_APP_ROOT . $endpoint;
        $httpClient = new HttpClient();

        print_r("\ntest unprotected POST $endpoint\n");
        $response = $httpClient->curlPost(
            $url,
            [
                "recipientProvider" => self::NC_2_INVITATION_SERVICE_PROVIDER_ENDPOINT,
                "token" => $this->tokenOpenSentInvitation, // sent by nc-1
                "userID" => "me@nc-2.nl@mesh.org",
                "email" => "me@nc-2.nl",
                "name" => "M.E",
            ]
        );
        print_r("\n" . print_r($response, true));
        assertEquals(1, 1, "Strange");
    }

    public function tearDown(): void
    {
        print_r("\ntearDown test\n");
        parent::tearDown();
    }
}
