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
        $this->assertEquals(-1, $b->whichSideIsCharacter(1, 2), 'Character shouldn\'t be in battle yet.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(1, 2, 3), 'Character shouldn\'t be in battle yet.');

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
        $this->assertEquals(0, $b->whichSideIsCharacter(1, 2), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(1, 2, 3), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(-1, 2), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(-1, -1, 3), 'Character should be in battle.');
        $this->assertEquals(0, $b->whichSideIsCharacter(-1, 2, -1), 'Character should be in battle.');

        // Should not return a side
        $this->assertEquals(-1, $b->whichSideIsCharacter(-1, 9), 'Character not in battle.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(-1, -1, 9), 'Character not should be in battle.');
        $this->assertEquals(-1, $b->whichSideIsCharacter(-1, 9, -1), 'Character not should be in battle.');
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

        $this->assertEquals(1, $b->whichSideIsKMAttackers($km1), 'km1 should be side 1. ' . $b->toString());
        $this->assertEquals(-1, $b->whichSideIsKMAttackers($km3), 'km3 should not be a side yet. ' . $b->toString());

        $b->processKM($km3);

        $this->assertEquals(1, $b->whichSideIsKMAttackers($km1), 'km1 should be side 1. ' . $b->toString());
        $this->assertEquals(3, $b->whichSideIsKMAttackers($km3), 'km3 should be side 3. ' . $b->toString());
    }

    /**
     * Tests the meging of sides.
     * 
     * @covers EveNN\Battle::mergeSides()
     */
    public function testMergeSides(): void
    {
        $b = new Battle();

        $side = new Side();
        $side->addCharacterIDs([1]);
        $side->addCorpIDs([2]);
        $side->addAllianceIDs([3]);
        $b->sides[] = $side;

        $side = new Side();
        $side->addCharacterIDs([3]);
        $side->addCorpIDs([4]);
        $side->addAllianceIDs([5]);
        $b->sides[] = $side;

        $this->assertEquals(2, count($b->sides), 'Wrong number of sides.');

        $b->mergeSides(0, 1);
        $this->assertEquals(1, count($b->sides), 'Side 1 was not purged');

        $this->assertTrue($b->sides[0]->hasCharacterID(3), 'Missing member.');
        $this->assertTrue($b->sides[0]->hasCorpID(4), 'Missing corp.');
        $this->assertTrue($b->sides[0]->hasAllianceID(5), 'Missing alliance.');
    }

    /**
     * Tests the ability to process a KM
     * 
     * @covers EveNN\Battle::autoMergeDetect()
     * 
     * @depends testWhichSideIsKMAttackers
     * @depends testwhichSideIsCharacter
     */
    public function testAutoMergeDetect(): void
    {
        // Km - baseline
        $km1 = new KM();
        $km1->victimID = 1;
        $km1->corpID = 1;
        $km1->allianceID = 1;
        $km1->attackers = [
            new Attacker(['character_id' => 4, 'corporation_id' => 5, 'alliance_id' => 5]),
            new Attacker(['character_id' => 7, 'corporation_id' => 5, 'alliance_id' => 5]),
            new Attacker(['character_id' => 8, 'corporation_id' => 5, 'alliance_id' => 5]),
            new Attacker(['character_id' => 9, 'corporation_id' => 6, 'alliance_id' => 6]),
            new Attacker(['character_id' => 10, 'corporation_id' => 6, 'alliance_id' => 6]),
            new Attacker(['character_id' => 11, 'corporation_id' => 6, 'alliance_id' => 6]),
        ];

        // KM - unrelated kill
        $km2 = new KM();
        $km2->victimID = 2;
        $km2->corpID = 2;
        $km2->allianceID = 2;
        $km2->attackers = [
            new Attacker(['character_id' => 100, 'corporation_id' => 7, 'alliance_id' => 7]),
            new Attacker(['character_id' => 101, 'corporation_id' => 7, 'alliance_id' => 7]),
            new Attacker(['character_id' => 102, 'corporation_id' => 7, 'alliance_id' => 7]),
            new Attacker(['character_id' => 103, 'corporation_id' => 7, 'alliance_id' => 7]),
            new Attacker(['character_id' => 104, 'corporation_id' => 7, 'alliance_id' => 7]),
            new Attacker(['character_id' => 105, 'corporation_id' => 7, 'alliance_id' => 7]),
        ];

        // KM - attackers overlapped with km2
        $km3 = new KM();
        $km3->victimID = 11;
        $km3->corpID = 12;
        $km3->allianceID = 13;
        $km3->attackers = [
            new Attacker(['character_id' => 200, 'corporation_id' => 8, 'alliance_id' => 8]),
            new Attacker(['character_id' => 201, 'corporation_id' => 8, 'alliance_id' => 8]),
            new Attacker(['character_id' => 202, 'corporation_id' => 8, 'alliance_id' => 8]),
            new Attacker(['character_id' => 203, 'corporation_id' => 7, 'alliance_id' => 7]),
        ];

        // KM - attackers overlapped with km1 and km2
        $km4 = new KM();
        $km4->victimID = 14;
        $km4->corpID = 15;
        $km4->allianceID = 16;
        $km4->attackers = [
            new Attacker(['character_id' => 200, 'corporation_id' => 5, 'alliance_id' => 5]),
            new Attacker(['character_id' => 201, 'corporation_id' => 7, 'alliance_id' => 7]),
        ];

        // KM - victims overlapped with km1 and km3
        $km5 = new KM();
        $km5->victimID = 1;
        $km5->corpID = 11;
        $km5->allianceID = 16;
        $km5->attackers = [
            new Attacker(['character_id' => 301, 'corporation_id' => 9, 'alliance_id' => 9]),
            new Attacker(['character_id' => 302, 'corporation_id' => 10, 'alliance_id' => 10]),
        ];

        $b = new Battle();

        $b->processKM($km1);
        $this->assertFalse($b->autoMergeDetect(0.1), 'Should not be any overlaps yet.' . $b->toString());

        $b->processKM($km2);
        $this->assertFalse($b->autoMergeDetect(0.1), 'Should not be any overlaps yet. ' . $b->toString());

        // Attackers: Should merge into an already existing side
        $b->processKM($km3);
        $overlap = $b->autoMergeDetect(0.1);
        $this->assertFalse($b->autoMergeDetect(0.1), 'Should not be any overlaps yet');

        // Attackers: Should merge into second side, but overlap with forth
        $b->processKM($km4);
        $overlap = $b->autoMergeDetect(0.1);
        $this->assertIsArray($overlap, 'Should have returned result. ' . $b->toString());
        $this->assertEquals(1, $overlap[0], "Side overlap {$overlap[0]} should be 1. " . $b->toString());
        $this->assertEquals(3, $overlap[1], "Side overlap {$overlap[1]} should be 3. " . $b->toString());

        // Victims: Should merge into first side, but overlap with third
        $b->processKM($km5);
        $overlap = $b->autoMergeDetect(0.1);
        $this->assertIsArray($overlap, 'Should have returned result. ' . $b->toString());
        $this->assertEquals(1, $overlap[0], "Side overlap {$overlap[0]} should be 1. " . $b->toString());
        $this->assertEquals(3, $overlap[1], "Side overlap {$overlap[1]} should be 3. " . $b->toString());
    }

    /**
     * Tests the ability to process a KM
     * 
     * @covers EveNN\Battle::processKM()
     * 
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
        $km1->success = TRUE;
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
     * Tests adding events.
     * 
     * @covers EveNN\Battle::addEvent()
     * 
     */
    public function testaddEvent(): void
    {
        $b = new Battle();
        $b->addEvent(Event::ARRIVE, 1000, 1);
        $b->addEvent(Event::DESTROY, 2000, 2);

        $this->assertIsInt($b->events[0]->type);
        $this->assertEquals(2, $b->events[1]->targetID);
    }

    /**
     * Tests whether a battle is considered major..
     * 
     * @covers EveNN\Battle::isMajor()
     * 
     */
    public function testIsMajor(): void
    {
        $b = new Battle();     
        $this->assertFalse($b->isMajor(5), 'Empty battle should not be major.');

        $side = new Side();
        $side->addCharacterIDs([1,2,3]);
        $b->sides[] = $side;
        $this->assertFalse($b->isMajor(5), 'One-sided 3-member battle should not be major.');

        $b->sides[0]->addCharacterIDs([5,6,7]);
        $this->assertFalse($b->isMajor(5), 'One-sided 6-member battle should not be major.');

        $side = new Side();
        $side->addCharacterIDs([8,9,10]);
        $b->sides[] = $side;
        $this->assertFalse($b->isMajor(5), 'Two-sided 6/3-member battle should not be major.');

        $b->sides[1]->addCharacterIDs([11,12,13]);
        $this->assertTrue($b->isMajor(5), 'Two-sided 6/6-member battle should be major.');
    }

    /**
     * Tests general high-level killmail processing.
     * 
     * @covers EveNN\Battle::processKM()
     * @covers EveNN\Battle::assess()
     * 
     * @depends testConflictDetection
     * @depends testMergeSides
     */
    public function testGeneral(): void
    {
        // Load up killmails
        $files = [
            'battle-01-a.json',
            'battle-01-b.json',
            'battle-01-c.json'
        ];
        $kms = [];
        foreach ($files as $f) {
            $kmStr = $this->getFile($f);
            $this->assertNotNull($kmStr, "{$f} failed to load.");

            $km = new KM($kmStr);
            $this->assertTrue($km->success, "{$f} parsing failed. Check JSON syntax.");

            $kms[] = $km;
        }

        // Process into battle
        $b = new Battle();
        foreach ($kms as $km) {
            $b->processKM($km);
        }

        // Test
        $this->assertEquals(2, count($b->sides), 'Wrong number of sides.');
        $this->assertEquals(strtotime('2021-05-09T12:57:41Z'), $b->latestKMTime, 'Wrong latest time.');
    }
}
