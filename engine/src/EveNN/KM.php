<?php

namespace EveNN;

use EveNN\Attacker;

/**
 * Killmail parser and structure.
 */
class KM
{
    /**
     * General flags.
     * 
     * @var bool $success TRUE if parsing was successful.
     */
    var bool $success = FALSE;
    var bool $isSolo;
    var bool $isAwox;
    var bool $isNPC;

    /**
     * General identifiers.
     * 
     * @var int $ID Killmail ID.
     * @var int $victimID The victim ID.
     * @var int $corpID The victim's corp ID.
     * @var int $allianceID The victim's alliance ID, if any.
     * @var int $systemID The solar system ID.
     * @var int $locationID The location ID (stargate, planet, etc)
     * @var int $shipID The ship type Id.
     */
    var $ID;
    var $victimID;
    var $corpID;
    var $allianceID;
    var $systemID;
    var $locationID;
    var $shipID;

    /**
     * @var array $loc The location in space.
     * @var array $time When the kill occurred (Unix timestamp)
     */
    var $loc;
    var $time;

    /**
     * @var array $attackers List of attacking people.
     */
    var $attackers = FALSE;

    /**
     * Constructor.
     * 
     * @param string $kmStr
     *   The JSON KM structure.
     */
    function __construct($kmStr = NULL)
    {
        if ($kmStr) {
            $data = json_decode($kmStr, TRUE);
            $data = isset($data['package']) ? $data['package'] : NULL;

            if ($data) {
                // Pull IDs
                $this->ID = $data['killID'];
                $this->victimID = $data['killmail']['victim']['character_id'];
                $this->corpID = $data['killmail']['victim']['corporation_id'];
                $this->allianceID = isset($data['killmail']['victim']['alliance_id']) ? $data['killmail']['victim']['alliance_id'] : -1;
                $this->systemID = $data['killmail']['solar_system_id'];
                $this->locationID = $data['zkb']['locationID'];
                $this->shipID = $data['killmail']['victim']['ship_type_id'];

                // Flags
                $this->isAwox = $data['zkb']['awox'];
                $this->isSolo = $data['zkb']['solo'];
                $this->isNPC = $data['zkb']['npc'];

                // Meta
                $this->loc = $data['killmail']['victim']['position'];
                $this->time = strtotime($data['killmail']['killmail_time']);

                // Attackers
                $this->attackers = [];
                foreach ($data['killmail']['attackers'] as $a) {
                    $this->attackers[] = new Attacker($a);
                };

                $this->success = TRUE;
            }
        }
    }

    /**
     * Returns the lists of alliances, corps, of who attacked.
     * 
     * @return array
     *   The alliances and corps:
     *     - corps: list of corp IDs
     *     - alliances: list of alliance IDs
     */
    function getAttackerAssociations()
    {
        $list = [
            'corps' => [],
            'alliances' => [],
            'members' => []
        ];

        foreach ($this->attackers as $a) {
            if ( $a->corpID ) { $list['corps'][] = $a->corpID; }
            if ( $a->allianceID ) { $list['alliances'][] = $a->allianceID; }
            if ( $a->attackerID ) { $list['members'][] = $a->attackerID; }
        }

        $list['corps'] = array_unique($list['corps']);
        $list['alliances'] = array_unique($list['alliances']);
        $list['members'] = array_unique($list['members']);

        return $list;
    }

    /**
     * Can this killmail be skipped?
     * 
     * @return bool
     *   TRUE if skippable.
     */
    function isSkippable()
    {
        return $this->isNPC;
    }
}
