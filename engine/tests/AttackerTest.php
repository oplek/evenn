<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use EveNN\KM;
use EveNN\Attacker;

/**
 * Tests the Attacker class.
 */
final class AttackerTest extends TestCase
{
    /**
     * Loads a test file.
     * 
     * @param string $filename
     *   The filename to load.
     * 
     * @return string 
     *   The file content.
     */
    function getFile($filename)
    {
        return file_get_contents('tests/data/' . $filename);
    }

    /**
     * Tests initial parsing.
     * 
     * @covers EveNN\Attacker:__constructor()
     */
    public function testAttacker(): void
    {
        $kmStr = $this->getFile('basic-01.json');
        $this->assertNotNull($kmStr, 'basic-01.json failed to load.');

        $km = new KM($kmStr);
        $this->assertTrue($km->success, 'basic-01.json parsing failed. Check JSON syntax.');

        $attacker = current($km->attackers);
        //print_r($km);
        //print_r($attacker);

        $this->assertIsInt($attacker->attackerID, 'Bad attackerID');
        $this->assertIsInt($attacker->corpID, 'Bad corpID');
        $this->assertIsInt($attacker->allianceID, 'Bad allianceID');
        $this->assertIsInt($attacker->shipID, 'Bad shipID');
        $this->assertIsInt($attacker->weaponID, 'Bad weaponID');

        $this->assertGreaterThan(0, $attacker->attackerID, 'Bad attackerID');
        $this->assertGreaterThan(0, $attacker->corpID, 'Bad corpID');
        $this->assertGreaterThan(0, $attacker->allianceID, 'Bad allianceID');
        $this->assertGreaterThan(0, $attacker->shipID, 'Bad shipID');
        $this->assertGreaterThan(0, $attacker->weaponID, 'Bad weaponID');
    }

    

}
