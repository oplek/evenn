<?php

/**
 * Bootstrap for running the fetcher.
 */

require 'vendor/autoload.php';

use EveNN\Fetcher;
use EveNN\Log;
use EveNN\Config;

chdir(__DIR__);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '64M');

print "Running fetcher...\n";
Config::updateConfig();

if ( Fetcher::run() ) {
    Log::log("Ran fetcher - total KMs: " . count(array_keys(Fetcher::getRaw())));
} else {
    Log::log("Fetcher offline.");
}

print "Complete\n";
