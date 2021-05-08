<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use EveNN\MemcacheClient;

/**
 * Tests the Memcache functionality.
 */
final class MemcachedTest extends TestCase
{
    /**
     * @covers EveNN\MemcacheClient::set()
     * @covers EveNN\MemcacheClient::get()
     * @covers EveNN\MemcacheClient::delete()
     */
    public function testGet(): void
    {
        $randkey = 'test_' . rand(0,1000000);
        $randValue = rand(0,1000000);

        MemcacheClient::set($randkey, $randValue);
        $this->assertEquals($randValue, MemcacheClient::get($randkey));
        $this->assertEquals($randValue, MemcacheClient::get('unused_key', $randValue)); 
        
        // Handles complex data?
        MemcacheClient::set('testa', ['a' => 1]);
        $this->assertEquals(1, MemcacheClient::get('testa')['a']);
        $this->assertEquals(1, MemcacheClient::get('testa', ['b' => 2])['a']);
        MemcacheClient::delete('$randkey');

        // Delete
        MemcacheClient::delete($randkey);
        $this->assertNull(MemcacheClient::get($randkey), 'Key was not deleted.');
    }

    /**
     * @covers EveNN\MemcacheClient::getClient()
     */
    public function testGetClient(): void
    {
        $this->assertInstanceOf(
            Memcached::class,
            MemcacheClient::getClient()
        );
    }

}

