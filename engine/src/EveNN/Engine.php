<?php

namespace EveNN;

use EveNN\Config;
use EveNN\Fetcher;
use EveNN\Battle;
use EveNN\Log;
use EveNN\MemcacheClient;
use EveNN\ESI;

/**
 * Main engine.
 */
class Engine
{
    /**
     * @var array $battles The recorded battles.
     */
    static array $battles = [];

    /**
     * Determines whether the engine is currently activated,
     *   according to the config.yml file.
     */
    static function isEngineActivated()
    {
        return Config::get('engine_active') === TRUE;
    }

    /**
     * Runs the main engine for processing killmails.
     * 
     * @return bool
     *   Whether successfuly ran.
     */
    static function run()
    {

        Config::updateConfig();
        if (!self::isEngineActivated()) {
            return false;
        }

        // Are we locked?
        $lock = MemcacheClient::get('proc_run', FALSE);
        if ($lock) {
            return FALSE;
        }

        self::$battles = MemcacheClient::get('battles', []);

        // Fetch the next batches of killmails.
        $numProcessed = 0;
        $kms = Fetcher::getRaw(TRUE);
        Log::log(count(array_keys($kms)) . " KMs to be processed.");
        foreach ($kms as $kmRaw) {
            $km = new KM($kmRaw);
            if ($km->isSkippable()) {
                continue;
            }

            // Does it relate to an existing battle?
            $foundBattle = FALSE;
            foreach (self::$battles as &$b) {
                if ($b->kmIsRelevant($km)) {
                    $foundBattle = TRUE;
                    $b->processKMFull($km);
                }
            }

            // If not, span a new battle.
            if (!$foundBattle) {
                $b = new Battle();
                $b->processKMFull($km);
                self::$battles[] = $b;

                Log::log("Created battle: " . $b->status());
            }

            $numProcessed++;
        }

        MemcacheClient::set('proc_run', FALSE);
        MemcacheClient::set('battles', self::$battles);

        Log::log("Processed {$numProcessed} KMs.");

        return TRUE;
    }

    /**
     * Stores the current battles into memcached.
     * Note: This is meant mostly for setting up tests.
     * 
     */
    static function storeBattles() {
        MemcacheClient::set('battles', self::$battles);
    }

    /**
     * Expires old battles.
     * 
     */
    static function expireBattles()
    {
        $temp = [];
        self::$battles = MemcacheClient::get('battles', []);

        // Only keep recent battles
        $expiration = time() - Config::get('max_battle_age');
        foreach (self::$battles as $b) {            
            if ($b->latestKMTime > $expiration) {
                $temp[] = $b;
            } else {
                Log::log("Expiring battle: " . $b->status());
            }
        }

        self::$battles = $temp;
        MemcacheClient::set('battles', self::$battles);
    }

    /**
     *  Updates the final JSON.
     **/
    static function updateJson()
    {
        Log::log("Updating json");

        $status = ESI::request('status', [], 900);

        $battlesOutput = [
            'brref' => [],
            'status' => $status['json'],
            'ships' => [],
            'sysref' => [],
            'corps' => [],
            'alliances' => []
        ];
        foreach (self::$battles as $i => $b) {
            if ($b->isMajorAndValid()) {
                $battlesOutput['brref'][] = $b->output($battlesOutput);
                Log::log("Battle {$i} is major... adding.");
            }
        }
        MemcacheClient::set('battle_report', gzencode(json_encode($battlesOutput), 9));
    }
}
