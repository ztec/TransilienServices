<?php
namespace TransilienService\TransilienControllerBundle\Tests\Entities;

use TransilienService\TransilienControllerBundle\Entities\Station;
use TransilienService\TransilienControllerBundle\Entities\TrainStop;

class TrainStopTest extends \PHPUnit_Framework_TestCase
{
    public function testTrainStopEntityAssign()
    {
        /**{"trainDock":null,
         * "trainHour":"13\/05\/2013 17:03",
         * "trainLane":"2",
         * "trainMention":null,
         * "trainMissionCode":"SARA",
         * "trainNumber":"148848",
         * "trainTerminus":"SQY",
         * "type":"R"} */
        $dat = array(
            'number'       => '6868767',
            'mission'      => 'SARA',
            'type'         => 'R',
            'terminusCode' => 'SQY',
            'trainMention' => 'Retarder',
            'lane'         => 2,
            'dock'         => null
        );

        $TrainStop = new TrainStop();

        foreach ($dat as $key => $value) {
            $TrainStop->$key = $value;
        }

        foreach ($dat as $key => $value) {
            $this->assertEquals($value, $TrainStop->$key);
        }

        return $TrainStop;

    }

    /**
     * @depends testTrainStopEntityAssign
     */
    public function testTrainStationExtendsTrain($trainStop)
    {

        $this->assertInstanceOf('TransilienService\TransilienControllerBundle\Entities\Train', $trainStop);
    }


}
