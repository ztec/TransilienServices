<?php
namespace TransilienService\TransilienControllerBundle\DAO;

use TransilienService\Adapters\Curl\Curl;
use TransilienService\Adapters\Curl\CurlAdapterFactory;

class TransilienDAOTest extends \Phpunit_Framework_TestCase
{


    /**
     * This test is here ot check that creating the DAO does not trigger curl call
     * @return TransilienDAO
     */
    public function testDAOServiceConstruct()
    {

        $curlFactoryMock = $this->getMockBuilder(
            'TransilienService\Adapters\Curl\CurlAdapterFactory',
            array('createCurl')
        )->disableOriginalConstructor()
            ->getMock();

        //We test that no curl is created (not curl call needed = no curl wrapper need)
        $curlFactoryMock->expects($this->never())
            ->method('createCurl');
    }


    public function getMocks()
    {

        $curlFactoryMock = $this->getMockBuilder(
            'TransilienService\Adapters\Curl\CurlAdapterFactory',
            array('createCurl')
        )->disableOriginalConstructor()
            ->getMock();

        $curlAdapterMock = $this->getMockBuilder(
            'TransilienService\Adapters\Curl\Curl',
            array('setOpts', 'exec')
        )->disableOriginalConstructor()
            ->getMock();


        //we define that the method 'createCurl' will be called only once, and set the returned value
        //to bypass the standard processing


        $transilienDAO = new TransilienDAO($curlFactoryMock);


        return array(
            'factory' => $curlFactoryMock,
            'curl'    => $curlAdapterMock,
            'dao'     => $transilienDAO
        );
    }


    /**
     * @depends testDAOServiceConstruct
     */
    public function testGetStations()
    {
        $mocks = $this->getMocks();

        $mocks['factory']->expects($this->once())
            ->method('createCurl')
            //we test the parameter to be the string expected
            ->with($this->equalTo('http://ods.ocito.com/ods/transilien/android'))
            ->will($this->returnValue($mocks['curl']));

        $that = $this;

        $mocks['curl']->expects($this->atLeastOnce())
            ->method('setOpt')
            ->will(
                $this->returnCallback(
                    function ($one, $two) use ($that) {
                        //Using CURLOPT_POSTFIELDS is a shortcut due to the simple adapter. Actually, the adapter
                        //constant should be used
                        if ($one == CURLOPT_POSTFIELDS) {
                            $postData = json_decode($two);
                            //Should test if json is correctly encoded and fail if not !!
                            //I test here the target. I ask stations, I expect the correct target
                            /*$that->assertEquals(
                                '/transilien/getAllGares',
                                $postData->target,
                                'Target used in POST JSON is wrong'
                            );*/
                            $that->assertEquals(
                                '/transilien/getAllGares',
                                $postData[0]->target,
                                'Target used in POST JSON is wrong'
                            );

                            //more time == more post fields checks !
                        } else {
                            //Don't care
                        }
                        return true; //I don't care of the returned result, this is a mock !
                    }
                )
            );

        $mocks['curl']->expects($this->once())
            ->method('exec')
            //we return fake data received from WS earlier
            ->will($this->returnValue(file_get_contents(__DIR__ . '/getAllGares.json')));

        $stationCollection = $mocks['dao']->getStations();


        $this->assertInstanceOf(
            'TransilienService\TransilienControllerBundle\Collections\StationsCollection',
            $stationCollection
        );


        //We check that the collection can be used as an array (in fact, this is not a DAO test)
        $this->assertInstanceOf('\ArrayAccess', $stationCollection);

        //test that all members are instance of Station entity
        foreach ($stationCollection as $station) {
            $this->assertInstanceOf('TransilienService\TransilienControllerBundle\Entities\Station', $station);
        }

    }

    /**
     * @depends testDAOServiceConstruct
     */
    public function testGetStation()
    {
        $mocks = $this->getMocks();

        $mocks['factory']->expects($this->once())
            ->method('createCurl')
            //we test the parameter to be the string expected
            ->with($this->equalTo('http://ods.ocito.com/ods/transilien/android'))
            ->will($this->returnValue($mocks['curl']));

        $that = $this;
        $mocks['curl']->expects($this->atLeastOnce())
            ->method('setOpt')
            ->will(
                $this->returnCallback(
                    function ($one, $two) use ($that) {
                        //Using CURLOPT_POSTFIELDS is a shortcut due to the simple adapter. Actually, the adapter
                        //constant should be used
                        if ($one == CURLOPT_POSTFIELDS) {
                            $postData = json_decode($two);
                            //Should test if json is correctly encoded and fail if not !!
                            //I test here the target. I ask stations, I expect the correct target
                            /*$that->assertEquals(
                                '/transilien/getAllGares',
                                $postData->target,
                                'Target used in POST JSON is wrong'
                            );*/
                            $that->assertEquals(
                                '/transilien/getAllGares',
                                $postData[0]->target,
                                'Target used in POST JSON is wrong'
                            );
                            //more time == more post fields checks !
                        } else {
                            //Don't care
                        }
                        return true; //I don't care of the returned result, this is a mock !
                    }
                )
            );

        $mocks['curl']->expects($this->once())
            ->method('exec')
            //we return fake data received from WS earlier
            ->will($this->returnValue(file_get_contents(__DIR__ . '/getAllGares.json')));


        $station = $mocks['dao']->getStation('BFM');

        $this->assertInstanceOf('TransilienService\TransilienControllerBundle\Entities\Station', $station);

        $this->assertEquals('BFM', $station->code);
    }

    /**
     * @depends testDAOServiceConstruct
     */
    public function testNextTrains()
    {
        $mocks = $this->getMocks();

        //Maybe the createCurl method will be invocked multiple times. As curl can be the url does not change
        //Curl stay the same.
        //A best test can be two curlMock created with diferent result
        $mocks['factory']->expects($this->atLeastOnce())
            ->method('createCurl')
            //we test the parameter to be the string expected
            ->with($this->equalTo('http://ods.ocito.com/ods/transilien/android'))
            ->will($this->returnValue($mocks['curl']));


        $mocks['curl']->expects($this->atLeastOnce())
            ->method('setOpt')
            ->will(
                $this->returnValue(true) //No complex test here, already tested
            );


        //the order may be a bit hard to respect. Try avoiding this if possible
        $mocks['curl']->expects($this->exactly(2))
            ->method('exec')
            //we return fake data received from WS earlier
            ->will(
                $this->onConsecutiveCalls(
                    file_get_contents(__DIR__ . '/getAllGares.json'), //first call should be stations fetch
                    file_get_contents(__DIR__ . '/getNextTrains.json') //second call should be nextTrains
                )
            );

        $stationFrom = $mocks['dao']->getStation('ARP');
        $stationTo   = $mocks['dao']->getStation('BFM');

        //a litle paranoiac but cost nothing !
        $this->assertInstanceOf('TransilienService\TransilienControllerBundle\Entities\Station', $stationFrom);
        $this->assertInstanceOf('TransilienService\TransilienControllerBundle\Entities\Station', $stationTo);

        $trainStopsCollection = $mocks['dao']->nextTrains($stationFrom, $stationTo);

        $this->assertInstanceOf(
            'TransilienService\TransilienControllerBundle\Collections\TrainStopCollection',
            $trainStopsCollection
        );

        //We check that the collection can be used as an array (in fact, this is not a DAO test)
        $this->assertInstanceOf('\ArrayAccess', $trainStopsCollection);

        //test that all members are instance of Station entity
        foreach ($trainStopsCollection as $trainStop) {
            $this->assertInstanceOf('TransilienService\TransilienControllerBundle\Entities\TrainStop', $trainStop);
        }
    }
}
