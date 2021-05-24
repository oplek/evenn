<?php

namespace EveNN;

/**
 * A battle event.
 */
class Event {
    const ARRIVE = 1;
    const DESTROY = 2;

    /**
     * @var int $ts Timestamp of event.
     * @var int $type Type of event.
     * @var int $targetID The target of the event (i.e. character/member ID)
     */
    var ?int $ts;
    var ?int $type;
    var ?int $targetID;

    /**
     * Constructor.
     * 
     * @param int $type
     *   Event Type.
     * @param int $ts
     *   Event timestamp.
     * @param int $targetID
     *   Target ID.
     */
    function __construct($type, $ts, $targetID) {
        $this->type = $type;
        $this->ts = $ts;
        $this->targetID = $targetID;
    }

    /**
     * Output structure to battle report.
     * 
     * @return array
     *   Structure
     */
    function output() {
        return [
            't' => $this->ts,
            'y' => $this->type,
            'i' => $this->targetID
        ];
    }

}