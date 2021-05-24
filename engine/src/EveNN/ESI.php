<?php

namespace EveNN;

use EveNN\Config;
use EveNN\Log;

/**
 * ESI Interface
 */
class ESI
{
    const BASEPATH = 'https://esi.evetech.net/latest/';
    const ONE_YEAR = 3.154e+7; //In seconds

    /**
     * Makes a request to ESI. Responses are cached into the ./cache folder
     * 
     * @param string $call
     *   The API Call.
     * @param array $params
     *   The API parameters.
     * @param bool|int $expires
     *   How long until the request expires?
     * @param int $depth
     *   Recursive depth.
     * 
     * @return array|bool
     *   Response structure.
     */
    public static function request($call, $params = [], $expires = FALSE, $depth = 0)
    {
        if ($depth > 5) {
            return FALSE;
        }

        $paramsStr = http_build_query($params);
        $filepath = self::cachePath($call, $params);

        // try to stagger the expirations if expires is not explicit)
        if ( $expires === FALSE ) {
            $expires = self::ONE_YEAR * 3;
        }

        // Load cached copy 
        if (file_exists($filepath) && time() - filemtime($filepath) < $expires) {
            $response = unserialize(gzdecode(file_get_contents($filepath)));
            $response['cached'] = TRUE;
            return $response;
        } 
        
        // Uncached fetch
        else {
            $response = self::curlGet(self::BASEPATH . "{$call}?{$paramsStr}");

            //Temporary ESI-side problem - try again after delay
            if (in_array($response['code'], ['500', '502', '503', '504'])) {
                Log::log("ESI Error (Recoverable): " . print_r($response, TRUE));

                // Try to delay and try again
                if ($response['code'] == '503') {
                    sleep(30);
                } else {
                    sleep(5);
                }
                return self::Request($call, $params, $expires, $depth + 1);
            }

            if ($response['code'] == '200') {
                // Cache
                $storeResponse = gzencode(serialize($response), 9);
                file_put_contents($filepath, $storeResponse);

                Log::log("Updating cache for {$call}:{$filepath} (" . strlen($storeResponse) . ')');

                // And return
                usleep(75000); //Rate-limit accessing ESI                
                return $response;
            } else {
                Log::log("ESI Error: " . print_r($response, TRUE));
                return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * Purges the cache for a request.
     * 
     * @param string $call
     *   The API Call.
     * @param array $params
     *   The API parameters.
     */
    static function purge($call, $params)
    {
        $filepath = self::cachePath($call, $params);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Generates cache file. Also auto-generates sub-directories.
     * 
     * @param string $call
     *   The API Call.
     * @param array $params
     *   The API parameters.
     */
    static function cachePath($call, $params)
    {
        $cachePath = Config::get('cache_path');
        $paramsStr = http_build_query($params);
        $hash = md5("{$call}_{$paramsStr}");
        preg_match('/^([a-z0-9]{2})(.+)$/', $hash, $parts);
        $hash = "{$parts[1]}/{$parts[2]}";
        @mkdir("{$cachePath}{$parts[1]}");
        return $cachePath . $hash;
    }

    /**
     * Basic Curl request.
     * 
     * @param string $uri
     *   The URI request.
     * 
     * @return array
     *   Basic response structure.
     */
    static function curlGet($uri)
    {
        //Log::log("Esi::CurlGet $uri\n");

        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_NOBODY, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Evenews');
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        //$headers = get_headers_from_curl_response($response);
        $json = @json_decode($response, TRUE);
        return [
            'uri' => $uri,
            'code' => $code,
            'response' => $response,
            'json' => $json,
            'fetched' => time()
        ];
    }
}
