<?php

namespace EveNN;

use EveNN\Config;
use EveNN\MemcacheClient;

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
        do {
            $max--;

            $raw = self::getRemote();

            $json = json_decode($raw, TRUE);
            $filename = microtime();
            if ( $json ) {                
                $list[$filename] = $raw;
                $flag = isset($json['package']['killID']) && $max >= 0;
            } else {
                $list[$filename] = "fail: {$raw}";
                $flag = FALSE;
            }
            usleep(50000);
        } while($flag);

        // Save
        MemcacheClient::set('raw', $list);
        MemcacheClient::set('fetcher_run', FALSE);

        return TRUE;
    }

    /**
     * Fetches an entry from remote server.
     * 
     * @return string
     *   Raw response.
     */
    function getRemote() {
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
    function getRaw($purge = FALSE) {
        $raw = MemcacheClient::get('raw', []);
        if ( $purge ) {
            MemcacheClient::delete('raw');
        }
        return $raw;
    }

}