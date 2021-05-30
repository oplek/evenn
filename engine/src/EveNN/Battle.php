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
     * Full processing. Combines all functional parts.
     * 
     * @param KM $km
     *   The kill mail.
     */
    function processKMFull($km) {
        $conflicts = $this->conflicts($km);
        if ( !$conflicts ) {
            $this->processKM($km);

            while($m = $this->autoMergeDetect()) {
                $this->mergeSides($m[0], $m[1]);
            }
        }
    }

    /**
     * Processes a killmail, if it's relevant to the battle.
     *   This assumes there's no same-side conflicts.
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
        if ( $km->time > $this->latestKMTime ) {
            $this->latestKMTime = $km->time;
        }

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
     * See whether one or more sides could be merged. This returns
     *   one instance. This should be ran multiple times
     * note This assumes the sides have no conflicts.
     * 
     * @param float $overlap
     *   Decimal-percentge overlap before it should be merged.
     * 
     * @return bool|array
     *   Two indexes of which sides can be merged.
     */
    function autoMergeDetect($overlapTreshold = FALSE) {
        $count = count($this->sides);
        if ( $overlapTreshold === FALSE ) {
            $overlapTreshold = Battle::OVERLAP_THRESHOLD;
        }

        for($i = 0; $i < $count; $i++) {
            for($i2 = $i + 1; $i2 < $count; $i2++) {
                $overlap = $this->sides[$i]->percentOverlap($this->sides[$i2]);
                if ( $overlap['max'] >= $overlapTreshold ) {
                    return [$i, $i2];
                }
            }
        }

        return FALSE;
    }

    /**
     * Merges two sides by index. Side b is discarded.
     * 
     * @param int $a
     *   Side a index.
     * @param int $b
     *   Side b index.
     */
    function mergeSides($a, $b) {
        $sideA = &$this->sides[$a];
        $sideB = &$this->sides[$b];
        $sideA->merge($sideB);
        array_splice($this->sides, $b, 1);
    }

    /**
     * Registers particiapnts in killmail.
     * 
     * @param KM $km
     *   The killmail.
     */
    function addParticipants(KM $km) {
        if ( !$km->success ) { return; }

        // Add vicitm
        if ( !isset($this->participants[$km->victimID])) {
            $this->participants[$km->victimID] = new Participant(
                $km->victimID,
                $km->corpID,
                $km->allianceID,
                $km->shipID
            );
        }

        // Add attackers
        foreach($km->attackers as $a) {
            if ( !isset($this->participants[$a->attackerID]) ) {
                $this->participants[$a->attackerID] = new Participant(
                    $a->attackerID,
                    $a->corpID,
                    $a->allianceID,
                    $a->shipID, 
                    $a->weaponID
                );
            }

            // Updates
            if ( !$this->participants[$a->attackerID]->weaponID && $a->weaponID ) {
                $this->participants[$a->attackerID]->weaponID = $a->weaponID;
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
            /*if ( $overlap['members'] > Battle::OVERLAP_THRESHOLD ||
                $overlap['corp'] > Battle::OVERLAP_THRESHOLD || 
                $overlap['alliance'] > Battle::OVERLAP_THRESHOLD ) {
                return $i;
            }*/
            if ( $overlap['max'] >= Battle::OVERLAP_THRESHOLD ) {
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
     * Determine whether this "battle" is worth mentioning.
     * 
     * @param int $threshold
     *   How many both sides have to have before it's "major".
     * 
     * @return bool
     *   TRUE if yes.
     */
    function isMajorAndValid($threshold = 2) {
        if ( count($this->sides) != 2 ) { return FALSE; }
        return count($this->sides[0]->memberIDs) > $threshold && count($this->sides[1]->memberIDs) > $threshold;
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

    /**
     * Simple battle status string.
     * 
     * @return string
     *   Status.
     */
    function status() {
        $sides = count($this->sides);
        $numParticipants = count(array_keys($this->participants));
        $age = time() - $this->latestKMTime;

        return "[S:{$sides} P:{$numParticipants} A:{$age}]";
    }

    /**
     * Generates a battle report JSON-compatible structure.
     * 
     * @param array &$global
     *   The global central structure.
     */
    function output(&$global) {

        $structure = [
            'ts' => $this->time,                // Start time
            'begin' => $this->time - 120,
            'end' => $this->latestKMTime,       // End time
            'sid' => $this->systemID,           // System ID
            'locid' => $this->locationID,       // Location ID
            //'loc' => $this->loc,              // Location
            'sides' => [],                      // Sides
            'chars' => [],
            'events' => [],                     // Events
            'event_types' => [
                'arrive' => Event::ARRIVE,
                'destroy' => Event::DESTROY,
            ]
        ];

        // Events
        foreach($this->events as $e) {
            $structure['events'][] = $e->output();
        }

        // Update global data
        if ( !isset($global['sysref'][$this->systemID]) ) {
            $global['sysref'][$this->systemID] = ExtendedData::lookupSystem($this->systemID);

            // Secondary lookup - star type
            $global['sysref'][$this->systemID]['star'] = 
                ExtendedData::lookupStar($global['sysref'][$this->systemID]['star_id']);
        }

        // Add sides
        foreach($this->sides as $side) {
            $s = [
                'actors' => [],
                'corps' => [],
                'alliances' => []
            ];

            // Build specific data for each participant
            foreach($side->memberIDs as $id) {
                if ( $id <= 0 ) { continue; }
                $participant = $this->participants[$id];
                $s['actors'][$id] = [
                    'i' => $participant->shipID,
                    't' => $this->time
                ];

                // Track character
                $structure['chars'][$id] = [
                    'ship_id' => $participant->shipID                    
                ];          
                if ( $participant->shipID != $participant->weaponID ) {
                    $structure['chars'][$id]['wep_id'] = $participant->weaponID;
                }      

                // Update central data for ship
                if ( !isset($global['ships'][$participant->shipID]) ) {
                    if ($participant->shipID > 0) {
                        $sdata = ExtendedData::lookupShip($participant->shipID);
                        $global['ships'][$participant->shipID] = [
                            'gid' => $sdata['group_id']
                        ];

                        // Group data
                        $gdata = ExtendedData::lookupItemGroup($sdata['group_id']);
                        $global['ships'][$participant->shipID]['name'] = $gdata['name'];  
                        $global['ships'][$participant->shipID]['cid'] = $sdata['category_id'];                      
                    }
                }
            }

            // Add corps
            foreach($side->corpIDs as $id) {
                if ( $id <= 0 ) { continue; }
                $s['corps'][] = $id;

                $data = ExtendedData::lookupCorp($id);
                if ( !isset($global['corps'][$id]) ) {
                    $global['corps'][$id] = $data;
                }
            }
            $s['corps'] = array_unique($s['corps']);

            // Add alliances
            foreach($side->allianceIDs as $id) {
                if ( $id <= 0 ) { continue; }
                $s['alliances'][] = $id;

                $data = ExtendedData::lookupAlliance($id);
                if ( !isset($global['alliances'][$id]) ) {
                    $global['alliances'][$id] = $data;
                }
            }
            $s['alliances'] = array_unique($s['alliances']);

            $structure['sides'][] = $s;
        }

        return $structure;
    }
}
