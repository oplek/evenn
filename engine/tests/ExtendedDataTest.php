<?php

require_once 'vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use EveNN\ExtendedData;
use EveNN\Config;

/**
 * Tests the ExtendedData class.
 */
final class ExtendedDataTest extends TestCase
{

    /**
     * Tests the ship lookup
     * 
     * @covers EveNN\ESI:lookupShip()
     * @covers EveNN\ESI:lookupSystem()
     * @covers EveNN\ESI:lookupCorp()
     * @covers EveNN\ESI:lookupAlliance()
     */
    public function testLookup(): void
    {
        Config::updateConfig();

        $data = ExtendedData::lookupShip('622', TRUE);
        $this->assertEquals('Stabber', $data['name'], "Bad ship lookup. \n" . print_r($data, TRUE));

        usleep(200000);

        $data = ExtendedData::lookupSystem('30004771', TRUE);
        $this->assertEquals('8RQJ-2', $data['name'], "Bad system lookup. \n" . print_r($data, TRUE));

        usleep(200000);

        $data = ExtendedData::lookupCorp('125490663', TRUE);
        $this->assertEquals('TnT Strong Hold', $data['name'], "Bad corp lookup. \n" . print_r($data, TRUE));

        usleep(200000);

        $data = ExtendedData::lookupAlliance('131511956', TRUE);
        $this->assertEquals('Tactical Narcotics Team', $data['name'], "Bad alliance lookup. \n" . print_r($data, TRUE));
    }
}
