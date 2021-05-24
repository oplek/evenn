<?php

namespace EveNN;

use EveNN\ESI;

/**
 * Centralizes looking up and storing external extended info. This acts as an interface
 *   between ESI and the rest of the system.
 */
class ExtendedData
{
    /**
     * @var array $ships Ship data.
     * @var array $itemGroups Item group data.
     * @var array $systems System data.
     * @var array $stars Star data.
     * @var array $corps Corp data.
     * @var array $alliances Alliance data.
     * 
     */
    static array $ships = [];
    static array $itemGroups = [];
    static array $systems = [];
    static array $stars = [];
    static array $corps = [];
    static array $alliances = [];

    /**
     * Look up data on a ship.
     * 
     * @param int $id
     *   Ship ID
     * @param bool $debug
     *   If true, include response data.
     * 
     * @return array
     *   Return data.
     */
    static function lookupShip($id, $debug = FALSE)
    {        
        if (isset(self::$ships[$id])) {
            return self::$ships[$id];
        } else {

            $data = Esi::Request("universe/types/{$id}/", []);
            self::$ships[$id] = [
                'name' => $data['json']['name'],
                'type_id' => $data['json']['type_id'],
                'group_id' => $data['json']['group_id']
            ];

            if ( $debug ) {
                self::$ships[$id]['response'] = $debug ? $data : NULL;
            }

            return self::$ships[$id];
        }
    }

    /**
     * Look up item group data.
     * 
     * @param int $id
     *   Group ID
     * @param bool $debug
     *   If true, include response data.
     * 
     * @return array
     *   Return data.
     */
    static function lookupItemGroup($id, $debug = FALSE)
    {        
        if (isset(self::$itemGroups[$id])) {
            return self::$itemGroups[$id];
        } else {

            $data = Esi::Request("universe/groups/{$id}/", []);
            self::$itemGroups[$id] = [
                'name' => $data['json']['name']
            ];

            if ( $debug ) {
                self::$itemGroups[$id]['response'] = $debug ? $data : NULL;
            }

            return self::$itemGroups[$id];
        }
    }

    /**
     * Look up data on a system.
     * 
     * @param int $id
     *   Ship ID
     * @param bool $debug
     *   If true, include response data.
     * 
     * @return array
     *   Return data.
     */
    static function lookupSystem($id, $debug = FALSE)
    {
        if (isset(self::$systems[$id])) {
            return self::$systems[$id];
        } else {

            $data = Esi::Request("universe/systems/{$id}/", []);
            self::$systems[$id] = [
                'name' => $data['json']['name'],
                'star_id' => $data['json']['star_id']
            ];

            if ( $debug ) {
                self::$systems[$id]['response'] = $debug ? $data : NULL;
            }

            return self::$systems[$id];
        }
    }

    /**
     * Look up data on a star type.
     * 
     * @param int $id
     *   Ship ID
     * @param bool $debug
     *   If true, include response data.
     * 
     * @return array
     *   Return data.
     */
    static function lookupStar($id, $debug = FALSE)
    {
        if (isset(self::$stars[$id])) {
            return self::$stars[$id];
        } else {

            $data = Esi::Request("universe/stars/{$id}/", []);

            self::$stars[$id] = [
                'radius' => $data['json']['radius'] / 1000, //In km
                'spectral_class' => $data['json']['spectral_class'],
                'type_id' => $data['json']['type_id'],
                'luminosity' => round($data['json']['luminosity'],3)
            ];

            if ( $debug ) {
                self::$stars[$id]['response'] = $debug ? $data : NULL;
            }

            return self::$stars[$id];
        }
    }

    /**
     * Look up data on a corp.
     * 
     * @param int $id
     *   Corp ID
     * @param bool $debug
     *   If true, include response data.
     * 
     * @return array
     *   Return data.
     */
    static function lookupCorp($id, $debug = FALSE)
    {
        if (isset(self::$corps[$id])) {
            return self::$corps[$id];
        } else {

            // https://esi.evetech.net/ui/#/Corporation/get_corporations_corporation_id
            $data = Esi::Request("corporations/{$id}/", []);
            self::$corps[$id] = [
                'name' => $data['json']['name']
            ];

            if ( $debug ) {
                self::$corps[$id]['response'] = $debug ? $data : NULL;
            }

            return self::$corps[$id];
        }
    }

    /**
     * Look up data on an alliance.
     * 
     * @param int $id
     *   Ship ID
     * @param bool $debug
     *   If true, include response data.
     * 
     * @return array
     *   Return data.
     */
    static function lookupAlliance($id, $debug = FALSE)
    {
        if (isset(self::$alliances[$id])) {
            return self::$alliances[$id];
        } else {

            // https://esi.evetech.net/ui/#/Alliance/get_alliances_alliance_id
            $data = Esi::Request("alliances/{$id}/", []);
            self::$alliances[$id] = [
                'name' => $data['json']['name']
            ];

            if ( $debug ) {
                self::$alliances[$id]['response'] = $debug ? $data : NULL;
            }

            return self::$alliances[$id];
        }
    }
}
