<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use EveNN\Engine;
use EveNN\Config;

/**
 * Tests the Memcache functionality.
 */
final class EngineTest extends TestCase
{

    /**
     * 
     * @covers EveNN\Engine:isEngineActivated()
     */   
    public function testEngineSwitch(): void 
    {
        Config::updateConfig('engine_active: true');
        $this->assertTrue(Engine::isEngineActivated(), 'isEngineActivated (active) failed.');

        Config::updateConfig('engine_active: false');
        $this->assertFalse(Engine::isEngineActivated(), 'isEngineActivated (inactive) failed.');
    }

}

