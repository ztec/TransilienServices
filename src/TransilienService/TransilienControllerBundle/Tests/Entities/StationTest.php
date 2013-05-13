<?php
namespace TransilienService\TransilienControllerBundle\Tests\Entities;

use TransilienService\TransilienControllerBundle\Entities\Station;

class StationTest extends \PHPUnit_Framework_TestCase
{
    public function testStationEntityAssign()
    {


        $dat = array(
            'id'   => 'BFM01',
            'code' => 'BFM',
            'name' => 'Gros batiement où ya plein de livre'
        );

        $Station       = new Station();
        $Station->id   = $dat['id'];
        $Station->code = $dat['code'];
        $Station->name = $dat['name'];

        $this->assertEquals($dat['id'], $Station->id);
        $this->assertEquals($dat['code'], $Station->code);
        $this->assertEquals($dat['name'], $Station->name);


    }

    public function testStationAttributes()
    {

        $this->assertClassHasAttribute('id', 'TransilienService\TransilienControllerBundle\Entities\Station');
        $this->assertClassHasAttribute('code', 'TransilienService\TransilienControllerBundle\Entities\Station');
        $this->assertClassHasAttribute('name', 'TransilienService\TransilienControllerBundle\Entities\Station');
    }
}
