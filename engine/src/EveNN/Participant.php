<?php

namespace EveNN;

use EveNN\ExtendedData;

/**
 * Battle participant.
 */
class Participant {
    /**
     * @var int $memberID The character/member ID.
     * @var string $name The character name.
     * @var int $corpID The character corp ID.
     * @var string $corpName The character corp name.
     * @var int $allianceID The character alliance ID.
     * @var string $allianceName The character alliance name.
     * @var int $shipID The character alliance ID.
     * @var string $shipName The character alliance name.
     * @var int $weaponID The weapon type.
     * @var string $weaponName The weapon name.
     */
    var int $memberID = -1;
    var string $name;
    var int $corpID = -1;
    var string $corpName;
    var int $allianceID = -1;
    var string $allianceName;
    var int $shipID = -1;
    var string $shipName;
    var int $weaponID = -1;
    var string $weaponName;

    /**
     * @var bool $isLoaded Is the character fully loaded?
     */
    var bool $isLoaded = FALSE;

    /**
     * Constructor
     * 
     * @param int $memberID
     *   Member/character ID.
     * @param int $corpID
     *   Corp ID.
     * @param int $allianceID
     *   Alliance ID.
     * @param int $shipID
     *   Ship type ID.
     * @param int $weaponID
     *   Weapon type Id.
     */
    function __construct(int $memberID, int $corpID, int $allianceID = -1, int $shipID = -1, int $weaponID = -1) {
        $this->memberID = $memberID;
        $this->corpID = $corpID;
        $this->allianceID = $allianceID;
        $this->shipID = $shipID;
        $this->weaponID = $weaponID;
    }

    /**
     * Loads ship name.
     * 
     * @return string
     *   Ship name.
     */
    function getShipName() {
        if ( $this->shipID ) {
            $data = ExtendedData::lookupShip($this->shipID);
            return $data['name'];
        }

        return '[no ID]';
    }

    /**
     * Loads corp name.
     * 
     * @return string
     *   Corp name.
     */
    function getCorp() {
        if ( $this->corpID ) {
            $data = ExtendedData::lookupCorp($this->corpID);
            return $data['name'];
        }

        return '[no ID]';
    }

    /**
     * Loads alliance name.
     * 
     * @return string
     *   Alliance name.
     */
    function getAlliance() {
        if ( $this->allianceID ) {
            $data = ExtendedData::lookupAlliance($this->allianceID);
            return $data['name'];
        }

        return '[no ID]';
    }

}