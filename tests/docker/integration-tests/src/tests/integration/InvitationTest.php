<?php

namespace tests\integration;

use Exception;
use OCA\Invitation\Service\MeshRegistry\MeshRegistryService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use tests\util\AppError;
use tests\util\HttpClient;
use tests\util\Util;

class InvitationTest extends TestCase
{
    private const NC_1_HOST = "http://nc-1.nl";
    private const NC_1_OCS_ROOT = "http://nc-1.nl/ocs/v2.php/invitation";
    // private const NC_1_ENDPOINT = "http://nc-1.nl/apps/invitation";
    private string $authToken = "";

    public function setUp(): void
    {
        print_r("\nsetUp test\n");
        parent::setUp();
        $this->authToken = $this->getAuthToken('admin', getenv('ADMIN_PASS'));
    }

    private function getAuthToken(string $user, string $pass): string
    {

        // curl -u admin:passw -H 'OCS-APIRequest: true' http://nc-1.nl/ocs/v2.php/core/getapppassword
        $url = self::NC_1_HOST . "/ocs/v2.php/core/getapppassword";
        $httpClient = new HttpClient();
        // OCS spec: token should be base64 encoded [user]:[passwd] string
        $basicAuthToken = base64_encode("{$user}:{$pass}");
        $headers = ["OCS-APIRequest: true"];
        $response = $httpClient->curlGet($url, $basicAuthToken, "", $headers);
        print_r("\nReceived app authentication token: " . print_r($response, true) . "\n");
        return $response['apppassword'];

    }

    public function testRouteFindInvitations()
    {
        try {
            $endpoint = self::NC_1_HOST . "/apps/invitation/invitations?status=accepted|withdrawn";
            print_r("\nTesting unprotected endpoint: {$endpoint}");
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet($endpoint);
            print_r($response);
            $this->assertTrue(Util::isTrue($response['success']), "GET {$endpoint} failed");
            print_r("\nFound invitations: " . print_r($response['data'], true) . "\n");
            return $response['data'];
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    /**
     * Test
     *  - route GET /invitations/{token}
     *  - method InvitationController findByToken(token)
     * 
     * @return string token
     */
    public function testRouteGetInvitations(): string
    {
        try {
            $testToken = getenv("TEST_UUID_1");
            $endpoint = "/invitations/{$testToken}";
            $url = self::NC_1_OCS_ROOT . "{$endpoint}";
            print_r("\nTesting protected endpoint: {$endpoint}\n");
            $httpClient = new HttpClient();
            $basicAuthToken = base64_encode("admin:{$this->authToken}");
            $response = $httpClient->curlGet($url, $basicAuthToken, "", ["OCS-APIRequest: true"]);
            $this->assertTrue(Util::isTrue($response['success']), "GET {$url} failed");
            $this->assertEquals($testToken, $response['data']['token'], "GET {$endpoint} failed\n");
            return $response['data']['token'];
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    public function tearDown(): void
    {
        print_r("\ntearDown test\n");
        parent::tearDown();
    }
}
