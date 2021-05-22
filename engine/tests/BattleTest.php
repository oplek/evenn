<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use EveNN\Battle;
use EveNN\KM;
use EveNN\Side;
use EveNN\Attacker;
use EveNN\Participant;
use EveNN\Event;

/**
 * Tests the Battle class.
 */
final class BattleTest extends TestCase
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
     * Tests the relevancy check.
     * 
     * @covers EveNN\Battle::kmIsRelevant()
     */
    public function testKmIsRelevant(): void
    {
        // New KM/Battle - relevant
        $km = new KM();
        $km->locationID = 1;
        $km->systemID = 1;
        $km->time = 7200;

        $b = new Battle();
        $this->assertTrue($b->kmIsRelevant($km), 'New battle should accept new km.');

        // Existing KM/battle - relevant
        $b->systemID = 1;
        $b->locationID = 1;
        $b->latestKMTime = 7200;
        $km->time = 7000;
        $this->assertTrue($b->kmIsRelevant($km), 'Existing battle should accept new km.');

        // Existing KM/battle - non-relevant (bad time)
        $b->systemID = 1;
        $b->locationID = 1;
        $b->latestKMTime = 7200;
        $km->time = 72000;
        $this->assertFalse($b->kmIsRelevant($km), 'Existing battle should not accept new km.');
        
        // Existing KM/battle - non-relevant (bad loc)
        $b->systemID = 1;
        $b->locationID = 2;
        $b->latestKMTime = 7200;
        $km->time = 7200;
        $this->assertFalse($b->kmIsRelevant($km), 'Existing battle should not accept new km.');
    }

    /**
     * Tests the ability to detect and store side IDs.
     * 
     * @covers EveNN\Battle::whichSideIsCharacter()
     */
    public function testwhichSideIsCharacter(): void
    {
        $b = new Battle();
        
        $this->assertEquals(-1, $b->whichSideIsCharacter(1), 'Character shouldn\'t be in battle yet.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(1,2), 'Character shouldn\'t be in battle yet.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(1,2,3), 'Character shouldn\'t be in battle yet.');

        $side = new Side();
        $side->addCharacterIDs([1]);
        $side->addCorpIDs([2]);
        $side->addAllianceIDs([3]);
        $b->sides[] = $side;

        // Km - baseline
        $km1 = new KM();
        $km1->victimID = 1;
        $km1->corpID = 2;
        $km1->allianceID = 3;
        $km1->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6])
        ];
        $b->processKM($km1);

        // Test permutations - member ID / corp ID / Alliance ID
        $this->assertEquals(0, $b->whichSideIsCharacter(1), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(1,2), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(1,2,3), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(-1,2), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(-1,-1,3), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(-1,2,-1), 'Character should be in battle.');
        
        // Should not return a side
        $this->assertEquals(-1, $b->whichSideIsCharacter(-1,9), 'Character not in battle.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(-1,-1,9), 'Character not should be in battle.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(-1,9,-1), 'Character not should be in battle.');
    }    

    /**
     * Tests whichSideIsKMAttackers.
     * 
     * @covers EveNN\Battle::whichSideIsKMAttackers()
     */
    public function testWhichSideIsKMAttackers(): void
    {
        // Km - baseline
        $km1 = new KM();
        $km1->victimID = 1;
        $km1->corpID = 2;
        $km1->allianceID = 3;
        $km1->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 7, 'corporation_id' => 6, 'alliance_id' => 6]),
        ];

        // KM - another kill on the victim corp
        $km2 = new KM();
        $km2->victimID = 2;
        $km2->corpID = 2;
        $km2->allianceID = 3;
        $km2->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 8, 'corporation_id' => 9, 'alliance_id' => 10]),
        ];

        // KM - unrelated
        $km3 = new KM();
        $km3->victimID = 11;
        $km3->corpID = 12;
        $km3->allianceID = 13;
        $km3->attackers = [
            new Attacker(['character_id' => 14, 'corporation_id' => 15, 'alliance_id' => 16]),
            new Attacker(['character_id' => 18, 'corporation_id' => 19, 'alliance_id' => 20]),
        ];

        $b = new Battle();
        $b->processKM($km1);

        $this->assertEquals(1, $b->whichSideIsKMAttackers($km1), 'km1 should be side 1. '. $b->toString());
        $this->assertEquals(-1, $b->whichSideIsKMAttackers($km3), 'km3 should not be a side yet. '. $b->toString());

        $b->processKM($km3);

        $this->assertEquals(1, $b->whichSideIsKMAttackers($km1), 'km1 should be side 1. '. $b->toString());
        $this->assertEquals(3, $b->whichSideIsKMAttackers($km3), 'km3 should be side 3. '. $b->toString());
    }

    /**
     * Tests the ability to process a KM
     * 
     * @covers EveNN\Battle::processKM()
     * @depends testwhichSideIsCharacter
     */
    public function testProcessKM(): void
    {
        // Km - baseline
        $km1 = new KM();
        $km1->victimID = 1;
        $km1->corpID = 2;
        $km1->allianceID = 3;
        $km1->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 7, 'corporation_id' => 6, 'alliance_id' => 6]),
        ];

        // KM - another kill on the victim corp
        $km2 = new KM();
        $km2->victimID = 2;
        $km2->corpID = 2;
        $km2->allianceID = 3;
        $km2->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 8, 'corporation_id' => 9, 'alliance_id' => 10]),
        ];      

        // KM - not related to first two
        $km3 = new KM();
        $km3->victimID = 11;
        $km3->corpID = 12;
        $km3->allianceID = 13;
        $km3->attackers = [
            new Attacker(['character_id' => 14, 'corporation_id' => 15, 'alliance_id' => 16]),
        ];     

        $b = new Battle();
        $b->processKM($km1);
        $this->assertEquals(0, $b->whichSideIsCharacter(1), 'Charcter 1 should be in side 0. ' . $b->toString());
        $this->assertEquals(1, $b->whichSideIsCharacter(4), 'Charcter 4 should be in side 1. ' . $b->toString());
        $this->assertEquals(2, count($b->sides), 'Battle should only have 2 sides. ' . $b->toString());

        $b->processKM($km2);       
        $this->assertEquals(0, $b->whichSideIsCharacter(1), 'Charcter 1 should be in side 0, reported ' . $b->whichSideIsCharacter(1) . "\n" . $b->toString());
        $this->assertEquals(1, $b->whichSideIsCharacter(4), 'Charcter 4 should be in side 1, reported ' . $b->whichSideIsCharacter(4) . "\n" . $b->toString());
        $this->assertEquals(1, $b->whichSideIsCharacter(8), 'Charcter 8 should be in side , reported1 ' . $b->whichSideIsCharacter(8) . "\n" . $b->toString());
        $this->assertEquals(-1, $b->whichSideIsCharacter(11), 'Charcter 11 should not be in a side yet, reported ' . $b->whichSideIsCharacter(11) . "\n" . $b->toString());
        $this->assertEquals(2, count($b->sides), 'Battle should only have 2 sides. ' . $b->toString());

        $b->processKM($km3);       
        $this->assertEquals(0, $b->whichSideIsCharacter(1), 'Charcter 1 should be in side 0. ' . $b->toString());
        $this->assertEquals(2, $b->whichSideIsCharacter(11), 'Charcter 11 should be in side 2. ' . $b->toString());
        $this->assertEquals(3, $b->whichSideIsCharacter(14), 'Charcter 14 should be in side 3. ' . $b->toString());
    } 
    
    /**
     * Tests conflict detection
     * 
     * @covers EveNN\Battle::conflictVictimIsSameSide()
     * 
     * @depends testWhichSideIsKMAttackers
     */
    public function testConflictDetection(): void
    {
        // Km - baseline
        $km1 = new KM();
        $km1->victimID = 1;
        $km1->corpID = 2;
        $km1->allianceID = 3;
        $km1->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 7, 'corporation_id' => 6, 'alliance_id' => 6]),
        ];

        // KM - attackers kill someone on same corp
        // (Ignore character 1000 ID)
        $km2 = new KM();
        $km2->victimID = 1000;
        $km2->corpID = 5;
        $km2->allianceID = 6;
        $km2->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 8, 'corporation_id' => 9, 'alliance_id' => 10]),
        ];      

        // KM - attackers kill someone on same alliance, but unregistered corp
        // (Ignore the character-8 corp/alliance)
        $km3 = new KM();
        $km3->victimID = 8;
        $km3->corpID = 1000;
        $km3->allianceID = 6;
        $km3->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 8, 'corporation_id' => 9, 'alliance_id' => 10]),
        ];    

        // KM - attackers kill and unknown
        $km4 = new KM();
        $km4->victimID = 1;
        $km4->corpID = 2;
        $km4->allianceID = 1000;
        $km4->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 8, 'corporation_id' => 9, 'alliance_id' => 10]),
        ];     

        $b = new Battle();
        $b->processKM($km1);

        $this->assertEquals(2, count($b->sides), 'Should be 2 sides. ' . $b->toString());        
        $sideIndexAttackers = $b->whichSideIsKMAttackers($km2);
        $sideIndexVictim = $b->whichSideIsCharacter($km2->victimID, $km2->corpID, $km2->allianceID);
        $this->assertTrue($b->conflictVictimIsSameSide($km2), "Victim 4({$km2->victimID}|{$km2->corpID}|{$km2->allianceID}) should be same corp as side 1. (v:{$sideIndexVictim} a:{$sideIndexAttackers})" . $b->toString());
        
        $this->assertTrue($b->conflictVictimIsSameSide($km3), "Victim 8({$km3->victimID}|{$km3->corpID}|{$km3->allianceID}) should be same alliance as side 1. " . $b->toString());
        $this->assertFalse($b->conflictVictimIsSameSide($km4), 'Victim should be unrelated. ' . $b->toString());
    }  

    /**
     * Tests adding participants.
     * 
     * @covers EveNN\Battle::addParticipants()
     * 
     */
    public function testaddParticipants(): void
    {
        // Km - baseline
        $km1 = new KM();
        $km1->victimID = 1;
        $km1->corpID = 2;
        $km1->allianceID = 3;
        $km1->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 6]),
            new Attacker(['character_id' => 7, 'corporation_id' => 6, 'alliance_id' => 6]),
        ];
        
        $b = new Battle();
        $b->addParticipants($km1);

        $this->assertEquals(3, count(array_keys($b->participants)), 'Incorrect participant count.');
        $this->assertEquals(2, $b->participants[1]->corpID);
        $this->assertEquals(7, $b->participants[7]->memberID);
        $this->assertIsInt($b->participants[1]->allianceID);     
    }

    /**
     * Test a basic battle with 4 attackers and 3 victims, in the 
     *   same location.
     * 
     * @covers EveNN\Battle::processKM()
     * @covers EveNN\Battle::assess()
     */
    public function testBattle01(): void
    {
        // Load up killmails
        $files = [
            'battle-01-a.json',
            'battle-01-b.json',
            'battle-01-c.json'
        ];
        $kms = [];
        foreach($files as $f) {
            $kmStr = $this->getFile($f);
            $this->assertNotNull($kmStr, "{$f} failed to load.");
    
            $km = new KM($kmStr);
            $this->assertTrue($km->success, "{$f} parsing failed. Check JSON syntax.");    
        
            $kms[] = $km;
        }

        // Process into battle


    }
}
