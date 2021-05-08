<?php

namespace EveNN;

use EveNN\Config;

/**
 * Main engine.
 */
class Engine {

    /**
     * Determines whether the engine is currently activated,
     *   according to the config.yml file.
     */
    static function isEngineActivated() {
        return Config::get('engine_active') === TRUE;
    }  

}