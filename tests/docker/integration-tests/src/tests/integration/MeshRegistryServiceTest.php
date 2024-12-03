<?php

namespace tests\integration;

use Exception;
use PHPUnit\Framework\TestCase;
use tests\util\HttpClient;
use tests\util\Util;

class MeshRegistryServiceTest extends TestCase
{
    private const NC_1_HOST = "http://nc-1.nl";
    private const NC_1_APP_ROOT = "http://nc-1.nl/apps/collaboration";
    private const NC_1_OCS_ROOT = "http://nc-1.nl/ocs/v2.php/collaboration";
    private string $authToken = "";
    private string $providerUuid = "";
    private string $nc1Domain = "";

    public function setUp(): void
    {
        print_r("\nsetUp test\n");
        parent::setUp();
        if($this->authToken == "") {
            $this->authToken = Util::getAuthToken(self::NC_1_HOST, 'admin', getenv('ADMIN_PASS'));
        }
        $this->nc1Domain = getenv('NC1_DOMAIN');
        $this->providerUuid = getenv('NC1_PROVIDER_UUID');
    }

    /**
     * Test the unprotected /providers endpoint
     * 
     */
    public function testProviders()
    {
        try {
            $endpoint = "/mesh-registry/providers";
            print_r("\ntesting unprotected endpoint GET $endpoint\n");
            $url = self::NC_1_APP_ROOT . $endpoint;
            $httpClient = new HttpClient();
            $response = $httpClient->curlGet($url);
            print_r("\n" . print_r($response, true) . "\n");

            $this->assertEquals(2, count($response['data']), "Nr of services is not what is expected.");
        } catch (Exception $e) {
            $this->fail($e->getTraceAsString());
        }
    }

    // public function testGetInvitationServiceProviderName()
    // {
    //     try {
    //         $invitationServiceProviderName = "NC 1 University";
    //         $endpoint = "/registry/name";
    //         print_r("\ntesting unprotected endpoint GET $endpoint\n");
    //         $url = self::NC_1_APP_ROOT . $endpoint;
    //         $httpClient = new HttpClient();
    //         $response = $httpClient->curlGet($url);
    //         print_r("\n" . print_r($response, true) . "\n");

    //         $this->assertEquals($invitationServiceProviderName, $response['data'], "GET $url failed");

    //     } catch (Exception $e) {
    //         $this->fail($e->getTraceAsString());
    //     }
    // }

    // /**
    //  * @depends testGetInvitationServiceProviderName
    //  */
    // public function testSetInvitationServiceProviderName()
    // {
    //     try {
    //         $invitationServiceProviderName = "NC 1 New Name";
    //         $endpoint = "/registry/name";
    //         print_r("\ntesting protected endpoint PUT $endpoint\n");
    //         $url = self::NC_1_OCS_ROOT . $endpoint;
    //         $httpClient = new HttpClient();
    //         $basicAuthToken = base64_encode("admin:{$this->authToken}");
    //         $response = $httpClient->curlPut($url, ["name" => $invitationServiceProviderName], $basicAuthToken, "", ["OCS-APIRequest: true"]);
    //         print_r("\n" . print_r($response, true) . "\n");

    //         $this->assertEquals($invitationServiceProviderName, $response['data'], "GET $url failed");

    //     } catch (Exception $e) {
    //         $this->fail($e->getTraceAsString());
    //     }
    // }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
