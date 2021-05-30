<?php

namespace EveNN;

/**
 * Memcache client
 */
class MemcacheClient {

    /**
     * The Memcached client.
     */
    protected static $client = NULL;

    /**
     * @var string $prefix The memcached storage prefix.
     */
    public static string $prefix = 'var_';
    
    /**
     * Initalize/gets the client.
     * 
     * @return Memcached
     *   The client.
     */
    static function getClient() {
        if ( !self::$client ) {
            self::$client = new \Memcached();
            self::$client->addServer('127.0.0.1', 11211);
            self::$client->setOption(\Memcached::OPT_COMPRESSION, TRUE);
        }
        return self::$client;
    }

    /**
     * Fetches a variable.
     * 
     * @param string $key
     *   The variable key.
     * @param mixed $default
     *   The default value.
     * 
     * @returns mixed
     *   The 
     */
    static function get($key, $default = NULL) {
        $val = self::getClient()->get(self::$prefix . $key);
        if ( $val === FALSE ) {
            return $default;
        }
        return $val;
    }

    /**
     * Sets a variable.
     * 
     * @param string $key
     *   The variable key.
     * @param mixed $value
     *   The value to store.
     * @param int $expires
     *   When the key will expire.
     * @see https://www.php.net/manual/en/memcached.expiration.php
     */
    static function set($key, $value, $expires = 0) {
        self::getClient()->set(self::$prefix . $key, $value, $expires);
    }

    /**
     * Deletes a variable
     * 
     * @param string $key
     *   The key to delete.
     */
    static function delete($key) {
        self::getClient()->delete(self::$prefix . $key);
    }
}