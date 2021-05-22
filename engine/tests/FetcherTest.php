<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use EveNN\Fetcher;
use EveNN\Config;

/**
 * Tests the Fetcher functionality.
 * Note: There may not be any actual killmails when running these tests. 
 */
final class FetcherTest extends TestCase
{
    /**
     * Tests remote fetch..
     * 
     * @covers EveNN\Fetcher:getRemote()
     */
    public function testGetRemote(): void
    {
       $raw = Fetcher::getRemote();
       $this->assertStringContainsString('package', $raw);

       // Is it json?
       $this->assertNotFalse(json_decode($raw, TRUE), 'Remote package not JSON.');

       sleep(1);
    }

    /**
     * Tests remote and local fetches, plus some structure.
     * Note: Most of the time, some of these tests should be disabled for
     *   convenience.
     * 
     * @covers EveNN\Fetcher:run()
     * @covers EveNN\Fetcher:getRaw()
     */
    public function testRunAndGet(): void
    {
        // Force it to run
        $this->assertTrue(Config::updateConfig("fetcher_active: true\nfetch_max: 2"), 'Config was not updated.');        
        $this->assertTrue(Fetcher::run(), 'Fetcher did not correctly activate.');
        $list = Fetcher::getRaw(TRUE); // .. and purge
        //$key = key($list);

        // This should have something in it
        $this->assertIsArray($list, 'Raw list was not a list.');
        //$this->assertNotNull($key, 'Raw list did not have an entry (unlikely)');

        // This should be empty
        $this->assertNull(key(Fetcher::getRaw()));

        // Test structure
        //$package = $list[$key];
        //$this->assertStringContainsString('package', $package);

        sleep(1);
    }
}

