<?php

namespace EveNN;

use EveNN\Config;
use EveNN\MemcacheClient;
use EveNN\Log;

/**
 * Remotely fetches and locally stores/fetches killmails.
 */
class Fetcher {

    /**
     * Fetches a batch of entries from ZKillboard.
     * 
     * @return bool
     *   TRUE if ran.
     */
    static function run() {
        // Not active
        if ( !Config::get('fetcher_active', FALSE) ) {
            return FALSE;
        }
        
        // Are we locked?
        $lock = MemcacheClient::get('fetcher_run', FALSE);
        if ( $lock ) { return FALSE; }

        // Lock 
        MemcacheClient::set('fetcher_run', TRUE, 120);

        // Get existing raw list
        $list = MemcacheClient::get('raw', []);

        $flag = TRUE;
        $max = Config::get('fetch_max', 10); // Max fetch per round
        $count = 0;
        do {
            $max--;

            $raw = self::getRemote();
            if ( $raw ) {
                //Log::log("... fetched KM");
            } else {
                Log::log("... failed fetched KM");
            }

            $json = json_decode($raw, TRUE);
            $filename = microtime();
            if ( $json ) {                
                $flag = isset($json['package']['killID']) && $max >= 0;

                if ( $flag ) {
                    $list[$filename] = $raw;
                    $count++;
                }
            } else {
                $list[$filename] = "fail: {$raw}";
                $flag = FALSE;
            }
            usleep(50000);
        } while($flag);

        // Save
        MemcacheClient::set('raw', $list);
        MemcacheClient::set('fetcher_run', FALSE);

        Log::log("{$count} KMs fetched.");

        return TRUE;
    }

    /**
     * Fetches an entry from remote server.
     * 
     * @return string
     *   Raw response.
     */
    static function getRemote() {
        return file_get_contents('https://redisq.zkillboard.com/listen.php');
    }

    /**
     * Returns the raw unprocessed list, and clears.
     * 
     * @param bool $purge
     *   Whether to purge the list aferwards.
     * 
     * @return array 
     *   List of raw, compressed entries.
     */
    static function getRaw($purge = FALSE) {
        $raw = MemcacheClient::get('raw', []);
        if ( $purge ) {
            MemcacheClient::delete('raw');
        }
        return $raw;
    }

}