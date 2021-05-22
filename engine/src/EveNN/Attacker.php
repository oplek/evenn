<?php

namespace EveNN;

/**
 * An instance of an attacker.
 */
class Attacker {
    /**
     * @var int $attackerID Attacker ID.
     * @var int $corpID Corporation ID.
     * @var int $allianceID Alliance ID, if any.
     * @var int $shipID Ship type ID.
     * @var int $weaponID Weapon type ID.
     */
    var int $attackerID;
    var int $corpID;
    var int $allianceID;
    var int $shipID;
    var int $weaponID;

    /**
     * @var float $percentDam What percentage of damage was done by this attacker?
     */
    var float $percentDam = 0;

    /**
     * Constructor
     * 
     * @param array $data
     *   The parsed data from the killmail for the attacker.
     * @param int $totalDam
     *   Total damage dealt in KM.
     */
    function __construct(array $data, $totalDam = NULL) {
        $this->attackerID = isset($data['character_id']) ? $data['character_id'] : 0;
        $this->corpID = isset($data['corporation_id']) ? $data['corporation_id'] : 0;
        $this->allianceID = isset($data['alliance_id']) ? $data['alliance_id'] : 0;
        $this->shipID = isset($data['ship_type_id']) ? $data['ship_type_id'] : 0;
        $this->weaponID = isset($data['weapon_type_id']) ? $data['weapon_type_id'] : 0;

        if ( $totalDam && isset($data['damage_done']) ) {
            $this->percentDam = $data['damage_done'] / $totalDam;
        }
    }
    
}