<?php

namespace EveNN;

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
     */
    var int $memberID;
    var string $name;
    var int $corpID;
    var string $corpName;
    var int $allianceID;
    var string $allianceName;

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
     */
    function __construct(int $memberID, int $corpID, int $allianceID = -1) {
        $this->memberID = $memberID;
        $this->corpID = $corpID;
        $this->allianceID = $allianceID;
    }

}