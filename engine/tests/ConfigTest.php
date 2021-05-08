<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use EveNN\Config;

/**
 * Tests the Config functionality.
 */
final class ConfigTest extends TestCase
{
    /**
     * @covers EveNN\Config:updateConfig()
     */
    public function testUpdateConfig(): void
    {
        $this->assertTrue(Config::updateConfig(), '"config.yml" did not load or parse correctly.');
        $this->assertIsNumeric(Config::get('build')); 

        // Manual load
        Config::updateConfig('flag: true');
        $this->assertTrue(Config::get('flag'), 'Test flag was not true.');
    }
}

