<?php

namespace EveNN;

use EveNN\Config;

/**
 * Basic internal logging.
 */
class Log {
    /**
     * Writes to the log, and limits to number of characters.
     */
    static function log($str) {
        $path = Config::get('log_path');
        if ( !Config::get('log_enable') ) {
            return;
        }

        if ( file_exists($path) ) {
            $content = file_get_contents($path);
        } else {
            $content = '';
        }
        
        $ts = date('g:ia');
        $str = "{$ts}: {$str}\n";
        $content .= $str;

        // Truncate file
        $size = 10000;
        $content = substr($content, strlen($content) - $size, $size);
        file_put_contents($path, $content);
    }
}