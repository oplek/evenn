<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use EveNN\KM;

/**
 * Tests the KM class.
 */
final class KMTest extends TestCase
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
     * @covers EveNN\KM:__constructor()
     */
    public function testKM(): void
    {
        $kmStr = $this->getFile('basic-01.json');
        $this->assertNotNull($kmStr, 'basic-01.json failed to load.');

        $km = new KM($kmStr);
        $this->assertTrue($km->success, 'basic-01.json parsing failed. Check JSON syntax.');

        $this->assertIsInt($km->ID, 'Bad ID');
        $this->assertIsInt($km->victimID, 'Bad victimID');
        $this->assertIsInt($km->corpID, 'Bad corpID');
        $this->assertIsInt($km->allianceID, 'Bad allianceID');
        $this->assertIsInt($km->systemID, 'Bad systemID');
        $this->assertIsInt($km->locationID, 'Bad locationID');
        $this->assertIsInt($km->shipID, 'Bad shipID');

        $this->assertIsInt($km->time, 'Bad time(1)');
        $this->assertGreaterThan(10000, $km->time, 'Bad time(2)');

        $this->assertIsBool($km->isAwox, 'Bad flag awox.');
        $this->assertIsBool($km->isSolo, 'Bad flag solo.');
        $this->assertIsBool($km->isNPC, 'Bad flag NPC.');

        $this->assertArrayHasKey('y', $km->loc, 'Bad location structure.');
        $this->assertIsFloat($km->loc['y'], 'Bad location value.');

        // Empty killmail
        $kmStr = $this->getFile('blank.json');
        $this->assertNotNull($kmStr, 'blank.json failed to load.');
        $km = new KM($kmStr);
        $this->assertFalse($km->success, 'Blank killmail should have failed.');
     
    }

    /**
     * Tests initial parsing.
     * 
     * @covers EveNN\KM:getAttackerAssociations()
     */
    public function testGetAttackerAssociations(): void
    {
        $kmStr = $this->getFile('solo-player-kill.json');
        $this->assertNotNull($kmStr, 'solo-player-kill.json failed to load.');

        $km = new KM($kmStr);
        $this->assertTrue($km->success, 'solo-player-kill.json parsing failed. Check JSON syntax.');

        $killerAssoc = $km->getAttackerAssociations();
        $this->assertIsInt($killerAssoc['corps'][0], 'Bad corp ID');
        $this->assertIsInt($killerAssoc['alliances'][0], 'Bad alliance ID');
        $this->assertIsInt($killerAssoc['members'][0], 'Bad member ID');
    }
}
