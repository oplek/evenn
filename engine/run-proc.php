<?php

/**
 * Bootstrap for running the killmail processing.
 */

require 'vendor/autoload.php';

use EveNN\Engine;
use EveNN\Log;
use EveNN\Config;

chdir(__DIR__);

print "Running processor...\n";
print getcwd() . "\n";
Config::updateConfig();

if ( Engine::run() ) {
    Log::log("Ran engine.");

    Engine::expireBattles();
    Engine::updateJson();
} else {
    Log::log("Processor offline.");
}

$mem = round(memory_get_peak_usage() / 1000 / 1000, 1);
Log::log("Complete (Mem: {$mem}MB)\n");