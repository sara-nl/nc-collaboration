<?php

namespace tests\util;

use Exception;
use SimpleXMLElement;

use function PHPUnit\Framework\isEmpty;

class HttpClient
{
    public function __construct()
    {
    }

    /**
     * Executes a POST request.
     *
     * @param string $url
     * @param array $params post fields
     * @param string $userName the user name to create a session user from
     * @return mixed returns an array in the standardized format:
     *  [
     *      'success' => true | false,
     *
     *      'data' => if success is true
     *          or
     *      'error_message' => if success is false
     *  ]
     * @throws HttpException
     */
    public function curlPost(string $url, array $params = [], bool $unprotected = false)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            $output = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if (!isset($output) || $output == false) {
                throw new Exception('curl_exec error > curl_getinfo: ' . print_r($info, true));
            }
            if ($unprotected) {
                return json_decode($output, true);
            }
            $ocs = new SimpleXMLElement($output);
            if ($ocs->meta->status == 'ok') {
                return Util::simplexmlToArray($ocs->data);
            } else {
                throw new Exception($ocs->meta->statuscode . '. ' . $ocs->meta->message);
            }
        } catch (Exception $e) {
            throw new Exception($e->getTraceAsString());
        }
    }

    /**
     * Executes a GET request.
     *
     * @param string $url
     * @param string $userName the user name to create a session user from
     * @return mixed returns an array in the standardized format:
     *  [
     *      'success' => true | false,
     *
     *      'data' => if success is true
     *          or
     *      'error_message' => if success is false
     *  ]
     * @throws HttpException
     */
    public function curlGet(
        string $url,
        string $basicAuthToken = "",
        string $simpleUserPass = "",
        array $headers = [],
        bool $returnHttpCode = false
    ) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $httpHeaders = [];
            if ($basicAuthToken != "") {
                array_push($httpHeaders, "Authorization: Basic {$basicAuthToken}");
            }
            if ($simpleUserPass != "") {
                curl_setopt($ch, CURLOPT_USERPWD, $simpleUserPass);
            }
            if (!empty($headers)) {
                foreach ($headers as $header) {
                    array_push($httpHeaders, $header);
                }
            }
            if (!empty($httpHeaders)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
            }
            $output = curl_exec($ch);
            $info = null;
            if ($returnHttpCode) {
                return curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            }
            $info = curl_getinfo($ch);
            curl_close($ch);
            if (!isset($output) || $output == false) {
                print_r("\ncurl_getinfo: " . print_r($info, true));
                throw new Exception('curl_exec output error, curl_getinfo: ' . print_r($info, true));
            }
            // unprotected route
            if ($basicAuthToken == "" && $simpleUserPass == "") {
                return json_decode($output, true);
            }
            $ocs = new SimpleXMLElement($output);
            if ($ocs->meta->status == 'ok') {
                return Util::simplexmlToArray($ocs->data);
            } else {
                throw new Exception($ocs->meta->statuscode . '. ' . $ocs->meta->message);
            }
        } catch (Exception $e) {
            throw new Exception($e->getTraceAsString());
        }
    }
}
