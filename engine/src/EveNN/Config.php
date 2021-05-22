<?php

namespace EveNN;

/**
 * Configuration management. The file is read-only.
 */
class Config {
    /**
     * @var array $config
     */
    static $config = FALSE;

    /**
     * Updates the current config from the config.yml file. This will be
     *   periodically polled.
     * 
     * @param string $newYaml
     *   New, manual configuration YAML. This overrides
     *   the file load, if set.
     * 
     * @return bool
     *   TRUE on success.
     */
    static function updateConfig($newYaml = NULL) {
        if ( $newYaml ) {
            $str = $newYaml;
        } else {
            $str = file_get_contents('config.yml');
        }
        $yaml = yaml_parse($str);  
        self::$config = $yaml;
        
        return $yaml !== FALSE && $yaml !== NULL;
    }

    /**
     * Fetches a variable from the configuration.
     * 
     * @param string $key
     *   The top-level key.
     * @param mixed $default
     *   The default value, if not exists.
     * 
     * @return mixed
     *   The value.
     */
    static function get($key, $default = NULL) {
        $val = self::$config && isset(self::$config[$key]) ? self::$config[$key] : NULL;
        if ( $val === NULL ) {
            return $default;
        }

        return $val;
    }
}