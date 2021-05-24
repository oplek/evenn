<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use EveNN\ESI;
use EveNN\Config;

/**
 * Tests the Attacker class.
 */
final class ESITest extends TestCase
{

    /**
     * Tests basic curl get.
     * 
     * @covers EveNN\ESI:curlGet()
     */
    public function testCurlGet(): void
    {
        Config::updateConfig();

        $uri = ESI::BASEPATH . 'status';
        $response = ESI::curlGet($uri);
        $this->assertEquals($uri, $response['uri']);
        $this->assertIsInt($response['code'], 'Response code was not a number.');
        $this->assertIsString($response['response'], 'Response was not a string.');
        sleep(1);
    }

    /**
     * Tests cache file path.
     * 
     * @covers EveNN\ESI:cachePath()
     */
    public function testCachePath(): void
    {
        Config::updateConfig();
        $cachePath = Config::get('cache_path');

        $this->assertEquals(Config::get('cache_path') . '7b/9baff5a91d3015b46cf2abeb5fa05b', ESI::cachePath('test', ['a' => 1]));
    }

    /**
     * Tests a full request.
     * 
     * @covers EveNN\ESI:request()
     * @covers EveNN\ESI:purge()
     * 
     * @depends testCurlGet
     * @depends testCachePath
     */
    public function testRequest(): void
    {
        Config::updateConfig();

        ESI::purge('status', []);

        // Basic status
        $response = ESI::request('status', [], 300);
        $this->assertIsArray($response['json'], "JSON was blank or badly structured. \n" . print_r($response, TRUE));
        $this->assertIsInt($response['json']['players'], "Player count was not set. \n" . print_r($response, TRUE));

        sleep(1);
        
        // Cache response
        $response = ESI::request('status', [], 300);
        $this->assertArrayHasKey('cached', $response, "Response should have been cached (A). \n" . print_r($response, TRUE));
        $this->assertTrue($response['cached'], "Response should have been cached (B). \n" . print_r($response, TRUE));
        $this->assertIsInt($response['json']['players'], "Player count was not set. \n" . print_r($response, TRUE));

        sleep(2);
        
        // Cache-expire response (on its own)
        $response = ESI::request('status', [], 1);
        $this->assertArrayNotHasKey('cached', $response, "Response should not have been cached (A). \n" . print_r($response, TRUE));
        $this->assertIsInt($response['json']['players'], "Player count was not set. \n" . print_r($response, TRUE));

        sleep(1);

        // Cache-purged response
        ESI::purge('status', []);
        $response = ESI::request('status', [], 300);
        $this->assertArrayNotHasKey('cached', $response, "Response should not have been cached (A). \n" . print_r($response, TRUE));
        $this->assertIsInt($response['json']['players'], "Player count was not set. \n" . print_r($response, TRUE));

    }
}
