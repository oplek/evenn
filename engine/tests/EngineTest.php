<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use EveNN\Engine;
use EveNN\Config;
use EveNN\Battle;
use EveNN\MemcacheClient;

/**
 * Tests the Memcache functionality.
 */
final class EngineTest extends TestCase
{
    /**
     * Update memcache prefix.
     */
    protected function setUp(): void {
        parent::setUp();

        MemcacheClient::$prefix = 'test_';
    }

    /**
     * Tests the engine activation switch.
     * 
     * @covers EveNN\Engine::isEngineActivated()
     */   
    public function testEngineSwitch(): void 
    {
        Config::updateConfig('engine_active: true');
        $this->assertTrue(Engine::isEngineActivated(), 'isEngineActivated (active) failed.');

        Config::updateConfig('engine_active: false');
        $this->assertFalse(Engine::isEngineActivated(), 'isEngineActivated (inactive) failed.');
    }

    /**
     * Tests the battle expirations.
     * 
     * @covers EveNN\Engine::expireBattles()
     * @covers EveNN\Engine::storeBattles()
     */   
    public function testExpireBattles(): void 
    {
        Config::updateConfig();

        $this->assertEquals('test_', MemcacheClient::$prefix, 'Memcache prefix is wrong.');
        $this->assertGreaterThan(10, Config::get('max_battle_age'), 'Config "max_battle_age" is invalid or improperly loaded.');

        Engine::$battles[] = new Battle();
        Engine::$battles[] = new Battle();
        Engine::$battles[] = new Battle();
        Engine::storeBattles();
        $this->assertEquals(3, count(Engine::$battles), 'Incorrect number of battles.');

        Engine::$battles[0]->latestKMTime = time();
        Engine::$battles[1]->latestKMTime = time() - Config::get('max_battle_age') * 5.0;
        Engine::$battles[2]->latestKMTime = time() - Config::get('max_battle_age') * 0.5;
        Engine::$battles[2]->locationID = 1234;
        Engine::storeBattles();

        Engine::expireBattles();

        $this->assertEquals(2, count(Engine::$battles), 'Incorrect number of battles.');
        $this->assertEquals(1234, Engine::$battles[1]->locationID, 'Battle expiration did not shift correctly.');
    }

}

