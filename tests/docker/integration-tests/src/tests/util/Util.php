<?php

namespace tests\util;

class Util
{
    public static function isTrue($val, $return_null = false): bool
    {
        $boolval = (is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val);
        return ($boolval === null && !$return_null ? false : $boolval);
    }

    /**
     * @param $xml
     * @return array
     * https://hotexamples.com/examples/-/-/simplexml_to_array/php-simplexml_to_array-function-examples.html
     */
    public static function simplexmlToArray($xml)
    {
        $ar = array();
        foreach ($xml->children() as $k => $v) {
            $child = self::simplexmlToArray($v);
            if (count($child) == 0) {
                $child = (string) $v;
            }
            foreach ($v->attributes() as $ak => $av) {
                if (!is_array($child)) {
                    $child = array("value" => $child);
                }
                $child[$ak] = (string) $av;
            }
            if (!array_key_exists($k, $ar)) {
                $ar[$k] = $child;
            } else {
                if (!is_string($ar[$k]) && isset($ar[$k][0])) {
                    $ar[$k][] = $child;
                } else {
                    $ar[$k] = array($ar[$k]);
                    $ar[$k][] = $child;
                }
            }
        }
        return $ar;
    }

    public static function getAuthToken(string $host, string $user, string $pass): string
    {
        $url =  "{$host}/ocs/v2.php/core/getapppassword";
        $httpClient = new HttpClient();
        // OCS spec: token should be base64 encoded [user]:[passwd] string
        $basicAuthToken = base64_encode("{$user}:{$pass}");
        $headers = ["OCS-APIRequest: true"];
        $response = $httpClient->curlGet($url, $basicAuthToken, "", $headers);
        return $response['apppassword'];
    }
}
