<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use EveNN\Side;

/**
 * Tests the Side class.
 */
final class SideTest extends TestCase
{

    /**
     * Tests the relevancy check.
     * 
     * @covers EveNN\Side::addCharacterIDs()
     * @covers EveNN\Side::addAllianceIDs()
     * @covers EveNN\Side::addCorpIDs()
     * @covers EveNN\Side::hasCharacterID()
     */
    public function testSideMembers(): void
    {
        $side = new Side();

        $this->assertFalse($side->hasCharacterID(1), 'Side should have been empty.');
        $side->addCharacterIDs([1,2]);
        $this->assertTrue($side->hasCharacterID(2), 'Side should have had ID.');  
        
        $this->assertFalse($side->hasCorpID(1), 'Side should have been empty.');
        $side->addCorpIDs([3,4]);
        $this->assertTrue($side->hasCorpID(3), 'Side should have had ID.');  

        $this->assertFalse($side->hasAllianceID(5), 'Side should have been empty.');
        $side->addAllianceIDs([5,6]);
        $this->assertTrue($side->hasAllianceID(5), 'Side should have had ID.');  
    }

    /**
     * Tests side comparisons.
     * 
     * @covers EveNN\Side::percentOverlap()
     */
    public function testPercentOverlap(): void
    {
        $sideA = new Side();
        $sideA->addCharacterIDs([1,2,3,4]);
        $sideA->addCorpIDs([1,2,3,4]);
        $sideA->addAllianceIDs([1,2,3,4]);

        // 50% overlap
        $sideB = new Side();
        $sideB->addCharacterIDs([3,4,5,6]);
        $sideB->addCorpIDs([3,4,5,6]);
        $sideB->addAllianceIDs([3,4,5,6]);

        // No overlap
        $sideC = new Side();
        $sideC->addCharacterIDs([7]);
        $sideC->addCorpIDs([7]);
        $sideC->addAllianceIDs([7]);

        // Empty
        $sideD = new Side();

        // Alliance-only overlap 
        $sideE = new Side();
        $sideE->addCharacterIDs([7]);
        $sideE->addCorpIDs([7]);
        $sideE->addAllianceIDs([4]);        

        $overlap = $sideA->percentOverlap($sideB);
        $this->assertEquals(0.5, $overlap['members']);
        $this->assertEquals(0.5, $overlap['corp']);
        $this->assertEquals(0.5, $overlap['alliance']);
        $this->assertEquals(0.5, $overlap['max']);

        $overlap = $sideA->percentOverlap($sideC);
        $this->assertEquals(0, $overlap['members']);
        $this->assertEquals(0, $overlap['corp']);
        $this->assertEquals(0, $overlap['alliance']);
        $this->assertEquals(0, $overlap['max']);

        $overlap = $sideA->percentOverlap($sideD);
        $this->assertEquals(0, $overlap['members']);
        $this->assertEquals(0, $overlap['corp']);
        $this->assertEquals(0, $overlap['alliance']);
        $this->assertEquals(0, $overlap['max']);

        // 100% of D alliance should be in A.
        $overlap = $sideA->percentOverlap($sideE);
        $this->assertEquals(0, $overlap['members']);
        $this->assertEquals(0, $overlap['corp']);
        $this->assertEquals(1.0, $overlap['alliance']);
        $this->assertEquals(1.0, $overlap['max']);

        // 25% of A alliance should be in D.
        $overlap = $sideE->percentOverlap($sideA);
        $this->assertEquals(0, $overlap['members']);
        $this->assertEquals(0, $overlap['corp']);
        $this->assertEquals(0.25, $overlap['alliance']);
        $this->assertEquals(0.25, $overlap['max']);
    }

    /**
     * Tests side merging
     * 
     * @covers EveNN\Side::merge()
     */
    public function testMerge(): void
    {
        $sideA = new Side();
        $sideA->addCharacterIDs([1,2,3,4]);
        $sideA->addCorpIDs([1,2,3,4]);
        $sideA->addAllianceIDs([1,2,3,4]);

        // 50% overlap
        $sideB = new Side();
        $sideB->addCharacterIDs([3,4,5,6]);
        $sideB->addCorpIDs([3,4,5,6]);
        $sideB->addAllianceIDs([3,4,5,6]);

        $sideA->merge($sideB);

        $this->assertTrue($sideA->hasCharacterID(6), 'Side A did not contain member 6');
        $this->assertTrue($sideA->hasAllianceID(6), 'Side A did not contain alliance 6');
        $this->assertTrue($sideA->hasCorpID(6), 'Side A did not contain corp 6');

        $this->assertFalse($sideA->hasCharacterID(8), 'Side A did contain member 8');
        $this->assertFalse($sideA->hasAllianceID(8), 'Side A did contain alliance 8');
        $this->assertFalse($sideA->hasCorpID(8), 'Side A did contain corp 8');
    }

}
