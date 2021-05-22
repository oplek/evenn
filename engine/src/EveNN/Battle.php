<?php

namespace EveNN;

use EveNN\Side;
use EveNN\Event;
use EveNN\Participant;

/**
 * A detected battle event, built over time.
 */
class Battle
{
    /**
     * @const MAX_BATTLE_TIMEOUT How many seconds until a battle is considered over?
     */
    const MAX_BATTLE_TIMEOUT = 3600;
    const OVERLAP_THRESHOLD = 0.1;

    /**
     * @var array $sides The detected sides to this battle.
     */
    var array $sides = [];

    /**
     * @var array $participants Who was involved, what were they flying, etc.
     * @var array $events What happened, and when?
     */
    var array $participants = [];
    var array $events = [];

    /**
     * @var int $locationID The battle locationID.
     * @var int $systemID The system ID. 
     */
    var ?int $locationID = NULL;  
    var ?int $systemID = NULL; 

    /**
     * Meta information.
     * @var int $time When did battle detection start? (unix timestamp)
     * @var int $latestKMTime When was the latest attached KM? (unit timestamp)
     */
    var ?int $time = NULL;
    var ?int $latestKMTime = NULL;

    /**
     * Assesses whether the killmail is relevant to the battle.
     * 
     * @param KM $km
     *   The killmail.
     * 
     * @return bool
     *   TRUE if relevant.
     */
    function kmIsRelevant($km) {
        // IF we're a blank slate, it's auto-relevant.
        if ( $this->locationID === NULL ) {
            return TRUE;
        }

        return 
            $this->locationID == $km->locationID && 
            $this->systemID == $km->systemID &&
            $km->time - $this->latestKMTime < Battle::MAX_BATTLE_TIMEOUT;
    }

    /**
     * Processes a killmail, if it's relevant to the battle.
     * 
     * @param KM $km
     *   The kill mail.
     */
    function processKM($km)
    {
        // Update timestamps
        if ( $this->time === NULL ) {
            $this->time = time();

            // Update meta
            $this->locationID = $km->locationID;
            $this->systemID = $km->systemID;            
        }
        $this->latestKMTime = $km->time;

        // Add to sides
        $foundVictimSide = $this->whichSideIsCharacter($km->victimID, $km->corpID, $km->allianceID);
        $foundAttackerSide = $this->whichSideIsKMAttackers($km);

        // Create new side for victim, if needed.
        if ( $foundVictimSide < 0 ) {
            $side = new Side();
            $side->addCharacterIDs([$km->victimID]);
            $side->addCorpIDs([$km->corpID]);
            $side->addAllianceIDs([$km->allianceID]);
            $this->sides[] = $side;
        } 

        // Create new side for attackers, if needed.
        $assoc = $km->getAttackerAssociations();
        if ( $foundAttackerSide < 0) {
            $side = new Side();            
            $side->addCharacterIDs($assoc['members']);
            $side->addCorpIDs($assoc['corps']);
            $side->addAllianceIDs($assoc['alliances']);
            $this->sides[] = $side;
        }
        
        // Merge it in
        else {
            $side = &$this->sides[$foundAttackerSide];
            $side->addCharacterIDs($assoc['members']);
            $side->addCorpIDs($assoc['corps']);
            $side->addAllianceIDs($assoc['alliances']);
        }

        // Track participants
        $this->addParticipants($km);
        $this->addEvent(Event::DESTROY, $km->time, $km->victimID);
    }

    /**
     * Registers particiapnts in killmail.
     * 
     * @param KM $km
     *   The killmail.
     */
    function addParticipants(KM $km) {
        // Add vicitm
        if ( !isset($this->participants[$km->victimID])) {
            $this->participants[$km->victimID] = new Participant(
                $km->victimID,
                $km->corpID,
                $km->allianceID
            );
        }

        // Add attackers
        foreach($km->attackers as $a) {
            if ( !isset($this->participants[$a->attackerID]) ) {
                $this->participants[$a->attackerID] = new Participant(
                    $a->attackerID,
                    $a->corpID,
                    $a->allianceID
                );
            }
        }
    }

    /**
     * Register an event.
     * 
     * @param int $type 
     *   Type.
     * @param int $ts
     *   Timestamp.
     * @param int $targetID
     *   Target ID.
     */
    function addEvent(?int $type, ?int $ts, ?int $targetID) {
        $this->events[] = new Event($type, $ts, $targetID);
    }

    /**
     * Returns index of which side this character ID belongs to, if it.
     *   This assumes character is only in one side.
     * 
     * @param int $id
     *   Character ID.
     * @param int $cid
     *   Corp ID.
     * @param int $aid
     *   Alliance ID.
     * 
     * @return int
     *   Side index.
     */
    function whichSideIsCharacter(int $id, int $cid = -1, int $aid = -1) {
        // Side members
        foreach($this->sides as $i => $side) {
            if ( $side->hasCharacterID($id) || $side->hasAllianceID($aid) || $side->hasCorpID($cid) ) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * Tries to determine which side a KM's attackers
     *   belong. This assumes they are only part of one side.
     * 
     * @param KM $km
     *   A killmail.
     * 
     * @return int
     *   Which side index, if any. -1 if not found.
     */
    function whichSideIsKMAttackers($km) {
        foreach($this->sides as $i => $s) {
            $assoc = $km->getAttackerAssociations();
            $overlap = $s->percentOverlapGeneral($assoc['members'], $assoc['corps'], $assoc['alliances']);
            if ( $overlap['members'] > Battle::OVERLAP_THRESHOLD ||
                $overlap['corp'] > Battle::OVERLAP_THRESHOLD || 
                $overlap['alliance'] > Battle::OVERLAP_THRESHOLD ) {
                return $i;
            }
        }

        return -1;
    }
    
    /**
     * Determine whether a KM would produce side conflicts.
     *   I.e. awoxing.
     * 
     * @param Side $side
     *   The other side.
     * 
     * @return bool
     *   Found conflict
     * 
     */
    function conflicts($km) {
        return $this->conflictVictimIsSameSide($km);
    }

    /**
     * Determines whether the victim is on the attacker's side.
     * 
     * @param KM $km
     *   The killmail.
     * 
     * @return bool
     *   TRUE if conflict.
     */
    function conflictVictimIsSameSide($km) {
        $sideIndexAttackers = $this->whichSideIsKMAttackers($km);
        $sideIndexVictim = $this->whichSideIsCharacter($km->victimID, $km->corpID, $km->allianceID);

        return $sideIndexVictim >= 0 && $sideIndexAttackers == $sideIndexVictim;
    }
    
    /**
     * Converts side to string.
     * 
     * @return string
     *   Human-readible string.
     */
    function toString() {
        $parts = ["Battle"];
        foreach($this->sides as $i => $s) {
            $parts[] = '  ' . $i . ': ' . $s->toString();
        }
        return implode("\n", $parts);
    }



}
