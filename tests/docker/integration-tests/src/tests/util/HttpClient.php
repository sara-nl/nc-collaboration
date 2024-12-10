<?php

namespace tests\util;

use CurlHandle;
use Exception;
use SimpleXMLElement;

class HttpClient
{
    public function __construct() {}

    /**
     * Executes a POST request.
     *
     * @param string $url
     * @param array $data the data to post
     * @param string $basicAuthToken
     * @param string $bearerAuthToken
     * @param string $simpleUserPass
     * @param array $headers
     * @param bool $ignoreHttpStatus do not throw exception if HTTP status is not OK, defaults to true
     * @return mixed returns an array in the standardized format:
     *  [
     *      'data' => if successfull,
     *          or
     *      'message' => in case of error,
     * 
     *      'http_status_code' => HTTP status code
     *  ]
     * @throws HttpException
     */
    public function curlPost(
        string $url,
        array $params = [],
        string $basicAuthToken = "",
        string $bearerAuthToken = "",
        string $simpleUserPass = "",
        array $headers = [],
        bool $ignoreHttpStatus = true,
    ) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            // OCS and non-OCS calls apparently need different encodings of the POST fields
            if ($basicAuthToken == "" && $bearerAuthToken == "" && $simpleUserPass == "") {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
            $httpHeaders = [];
            if ($basicAuthToken != "") {
                array_push($httpHeaders, "Authorization: Basic {$basicAuthToken}");
            }
            if ($bearerAuthToken != "") {
                array_push($httpHeaders, "Authorization: Bearer {$bearerAuthToken}");
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
            $headers = [];
            $this->fillResponseHeaders($ch, $headers);

            $output = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if (!isset($output) || $output == false) {
                throw new Exception('curl_exec error > curl_getinfo: ' . print_r($info, true));
            }

            // Process xml or json output and return the result
            try {
                $ocs = new SimpleXMLElement($output);
                if ($ocs->meta->status == 'ok') {
                    $result = Util::simplexmlToArray($ocs->data);
                    $result['http_status_code'] = $httpCode;
                    $result['headers'] = $headers;
                    return $result;
                } else if ($ignoreHttpStatus) { // return the result regardless of the HTTP status
                    $result = Util::simplexmlToArray($ocs->data);
                    $result['http_status_code'] = $httpCode;
                    $result['headers'] = $headers;
                    return $result;
                } else {
                    throw new Exception($ocs->meta->statuscode . '. ' . $ocs->meta->message);
                }
            } catch (Exception $e) {
                // just try json next
            }
            $decoded = json_decode($output, true);
            if (isset($decoded)) {
                $decoded['http_status_code'] = $httpCode;
                $decoded['headers'] = $headers;
                return $decoded;
            }
            $result = [];
            $result['data'] = $output;
            $result['http_status_code'] = $httpCode;
            $result['headers'] = $headers;
            return $result;
        } catch (Exception $e) {
            print_r($output);
            print_r($info);
            throw new Exception($e->getTraceAsString());
        }
    }

    /**
     * Executes a PATCH request.
     *
     * @param string $url
     * @param array $data the properties to patch
     * @param string $basicAuthToken
     * @param string $bearerAuthToken
     * @param string $simpleUserPass
     * @param array $headers
     * @param bool $ignoreHttpStatus do not throw exception if HTTP status is not OK, defaults to true
     * @return mixed returns an array in the standardized format:
     *  [
     *      'data' => if successfull,
     *          or
     *      'message' => in case of error,
     * 
     *      'http_status_code' => HTTP status code
     *  ]
     * @throws HttpException
     */
    public function curlPatch(
        string $url,
        array $params = [],
        string $basicAuthToken = "",
        string $bearerAuthToken = "",
        string $simpleUserPass = "",
        array $headers = [],
        bool $ignoreHttpStatus = true,
    ) {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            // OCS and non-OCS calls apparently need different encodings of the PATCH fields
            if ($basicAuthToken == "" && $bearerAuthToken == "" && $simpleUserPass == "") {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
            $httpHeaders = [];
            if ($basicAuthToken != "") {
                array_push($httpHeaders, "Authorization: Basic {$basicAuthToken}");
            }
            if ($bearerAuthToken != "") {
                array_push($httpHeaders, "Authorization: Bearer {$bearerAuthToken}");
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
            $headers = [];
            $this->fillResponseHeaders($ch, $headers);

            $output = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if (!isset($output) || $output == false) {
                throw new Exception('curl_exec error > curl_getinfo: ' . print_r($info, true));
            }

            // Process xml or json output and return the result
            try {
                $ocs = new SimpleXMLElement($output);
                if ($ocs->meta->status == 'ok') {
                    $result = Util::simplexmlToArray($ocs->data);
                    $result['http_status_code'] = $httpCode;
                    $result['headers'] = $headers;
                    return $result;
                } else if ($ignoreHttpStatus) { // return the result regardless of the HTTP status
                    $result = Util::simplexmlToArray($ocs->data);
                    $result['http_status_code'] = $httpCode;
                    $result['headers'] = $headers;
                    return $result;
                } else {
                    throw new Exception($ocs->meta->statuscode . '. ' . $ocs->meta->message);
                }
            } catch (Exception $e) {
                // just try json next
            }
            $decoded = json_decode($output, true);
            if (isset($decoded)) {
                $decoded['http_status_code'] = $httpCode;
                $decoded['headers'] = $headers;
                return $decoded;
            }
            $result = [];
            $result['data'] = $output;
            $result['http_status_code'] = $httpCode;
            $result['headers'] = $headers;
            return $result;
        } catch (Exception $e) {
            print_r($output);
            print_r($info);
            throw new Exception($e->getTraceAsString());
        }
    }

    // /**
    //  * Executes a PUT request.
    //  *
    //  * @param string $url
    //  * @param array $params post fields
    //  * @param string $userName the user name to create a session user from
    //  * @return mixed returns an array in the standardized format:
    //  *  [
    //  *      'data' => if successfull
    //  *          or
    //  *      'message' => in case of error
    //  *  ]
    //  * @throws HttpException
    //  */
    // public function curlPut(
    //     string $url,
    //     array $params = [],
    //     string $basicAuthToken = "",
    //     string $simpleUserPass = "",
    //     array $headers = [],
    // ) {
    //     try {
    //         $ch = curl_init();
    //         curl_setopt($ch, CURLOPT_URL, $url);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    //         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    //         // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    //         $httpHeaders = [];
    //         if ($basicAuthToken != "") {
    //             array_push($httpHeaders, "Authorization: Basic {$basicAuthToken}");
    //         }
    //         if ($simpleUserPass != "") {
    //             curl_setopt($ch, CURLOPT_USERPWD, $simpleUserPass);
    //         }
    //         if (!empty($headers)) {
    //             foreach ($headers as $header) {
    //                 array_push($httpHeaders, $header);
    //             }
    //         }
    //         if (!empty($httpHeaders)) {
    //             curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    //         }
    //         $output = curl_exec($ch);
    //         $info = curl_getinfo($ch);
    //         curl_close($ch);
    //         if (!isset($output) || $output == false) {
    //             throw new Exception('curl_exec error > curl_getinfo: ' . print_r($info, true));
    //         }
    //         // assume unprotected route if no credentials are provided
    //         if ($basicAuthToken == "" && $simpleUserPass == "") {
    //             return json_decode($output, true);
    //         }
    //         $ocs = new SimpleXMLElement($output);
    //         return Util::simplexmlToArray($ocs->data);
    //     } catch (Exception $e) {
    //         throw new Exception($e->getTraceAsString());
    //     }
    // }

    /**
     * Executes a GET request.
     *
     * @param string $url
     * @param string $basicAuthToken
     * @param string $bearerAuthToken
     * @param string $simpleUserPass
     * @param array $headers
     * @param bool $ignoreHttpStatus do not throw exception if HTTP status is not OK, defaults to true
     * @return mixed returns an array in the standardized format:
     *  [
     *      'data' => if successfull,
     *          or
     *      'message' => in case of error,
     * 
     *      'http_status_code' => HTTP status code
     *  ]
     * @throws HttpException
     */
    public function curlGet(
        string $url,
        string $basicAuthToken = "",
        string $bearerAuthToken = "",
        string $simpleUserPass = "",
        array $headers = [],
        bool $ignoreHttpStatus = true,
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
            if ($bearerAuthToken != "") {
                array_push($httpHeaders, "Authorization: Bearer {$bearerAuthToken}");
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
            $headers = [];
            $this->fillResponseHeaders($ch, $headers);

            $output = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $info = curl_getinfo($ch);
            curl_close($ch);
            if (!isset($output) || $output == false) {
                print_r("\ncurl_getinfo: " . print_r($info, true));
                throw new Exception('curl_exec output error, curl_getinfo: ' . print_r($info, true));
            }

            // Process xml or json output and return the result
            try {
                $ocs = new SimpleXMLElement($output);
                if ($ocs->meta->status == 'ok') {
                    $result = Util::simplexmlToArray($ocs->data);
                    $result['http_status_code'] = $httpCode;
                    $result['headers'] = $headers;
                    return $result;
                } else if ($ignoreHttpStatus) { // return the result regardless of the HTTP status
                    $result = Util::simplexmlToArray($ocs->data);
                    $result['http_status_code'] = $httpCode;
                    $result['headers'] = $headers;
                    return $result;
                } else {
                    throw new Exception($ocs->meta->statuscode . '. ' . $ocs->meta->message);
                }
            } catch (Exception $e) {
                // just try json next
            }
            $decoded = json_decode($output, true);
            if (isset($decoded)) {
                $decoded['http_status_code'] = $httpCode;
                $decoded['headers'] = $headers;
                return $decoded;
            }
            $result = [];
            $result['data'] = $output;
            $result['http_status_code'] = $httpCode;
            $result['headers'] = $headers;
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getTraceAsString());
        }
    }

    /**
     * Fills the specified headers array with all response headers
     * @param CurlHandle $ch
     * @param array $headers
     * @return void
     */
    private function fillResponseHeaders(CurlHandle $ch, &$headers): void
    {
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) { // ignore invalid headers
                    return $len;
                }
                $headers[trim($header[0])] = trim($header[1]);

                return $len;
            }
        );
    }
}
