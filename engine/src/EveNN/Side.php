<?php

namespace EveNN;

/**
 * One side of a battle.
 */
class Side {
    /**
     * @var array $members List of member IDs.
     * @var array $corpIDs List of corporatin IDs.
     * @var array $allianceIDs List of alliance IDs.
     */
    var array $memberIDs = [];
    var array $corpIDs = [];
    var array $allianceIDs = [];

    /**
     * Adds a list of character IDs to the current side.
     * 
     * @param array $ids
     *   List of character IDs.
     */
    function addCharacterIDs(array $ids) {
        $this->memberIDs = array_merge($this->memberIDs, $ids);
        $this->memberIDs = array_unique($this->memberIDs);
    }

    /**
     * Adds a list of corp IDs to the current side.
     * 
     * @param array $ids
     *   List of corp IDs.
     */
    function addCorpIDs(array $ids) {
        $this->corpIDs = array_merge($this->corpIDs, $ids);
        $this->corpIDs = array_unique($this->corpIDs);
    }

    /**
     * Adds a list of alliance IDs to the current side.
     * 
     * @param array $ids
     *   List of alliance IDs.
     */
    function addAllianceIDs(array $ids) {
        $this->allianceIDs = array_merge($this->allianceIDs, $ids);
        $this->allianceIDs = array_unique($this->allianceIDs);
    }

    /**
     * Returns true if the side has an ID.
     * 
     * @param int $id
     *   Character ID.
     * 
     * @return bool
     *   TRUE if contains.
     */
    function hasCharacterID(int $id) {
        return in_array($id, $this->memberIDs);
    }

    /**
     * Returns true if the side has an ID.
     * 
     * @param int $id
     *   Corp ID.
     * 
     * @return bool
     *   TRUE if contains.
     */
    function hasCorpID(int $id) {
        return in_array($id, $this->corpIDs);
    }

    /**
     * Returns true if the side has an ID.
     * 
     * @param int $id
     *   Alliance ID.
     * 
     * @return bool
     *   TRUE if contains.
     */
    function hasAllianceID(int $id) {
        return in_array($id, $this->allianceIDs);
    }

    /**
     * Returns the percentage (decimal) overlap with the
     *   side, and another, by alliance and corp.
     * 
     * @param Side &$side
     *   The other side.
     * 
     * @return array
     *   Overlaps by type.
     */
    function percentOverlap($side) {
        return $this->percentOverlapGeneral($side->memberIDs, $side->corpIDs, $side->allianceIDs);        
    }

    /**
     * Determines percentage overlaps. How many in other are in this?
     * 
     * @param array $memberIDs
     *   List of member IDs.
     * @param array $corpIDs
     *   List of corporation IDs.
     * @param array $allianceIDs
     *   List of alliance IDs.
     * 
     * @return array
     *   Overlaps by type.
     */
    function percentOverlapGeneral($memberIDs, $corpIDs, $allianceIDs) {
        $data = [
            'members' => 0,
            'corp' => 0,
            'alliance' => 0
        ];

        foreach($memberIDs as $id) {
            if ( $this->hasCharacterID($id) ) {
                $data['members'] ++;
            }             
        }
        foreach($corpIDs as $cid) {
            if ( $this->hasCorpID($cid) ) {
                $data['corp'] ++;
            }             
        }
        foreach($allianceIDs as $aid) {
            if ( $this->hasAllianceID($aid) ) {
                $data['alliance'] ++;
            }             
        }

        $cmembers = count($memberIDs);
        $ccorp = count($corpIDs);
        $calliance = count($allianceIDs);
        
        if ( $cmembers > 0 ) { $data['members'] /= $cmembers; }
        if ( $ccorp > 0 ) { $data['corp'] /= $ccorp; }
        if ( $calliance > 0 ) { $data['alliance'] /= $calliance; }

        $data['max'] = max($data['members'], $data['corp'], $data['alliance']);
       
        return $data;
    }


    /**
     * Merges the provided side into this one.
     * 
     * @param Side &$side
     *   Another side.
     */
    function merge($side) {
        $this->addCharacterIDs($side->memberIDs);
        $this->addCorpIDs($side->corpIDs);
        $this->addAllianceIDs($side->allianceIDs);
    }

    /**
     * Converts side to string.
     * 
     * @return string
     *   Human-readible string.
     */
    function toString() {
        return 'Side M[' . implode(',', $this->memberIDs) . '] C[' . implode(',', $this->corpIDs) . '] A[' . implode(',', $this->allianceIDs) . ']';
    }

}