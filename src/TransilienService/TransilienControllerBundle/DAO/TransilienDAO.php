<?php
namespace TransilienService\TransilienControllerBundle\DAO;

use TransilienService\Adapters\Curl\CurlAdapterFactory;
use TransilienService\TransilienControllerBundle\Collections\StationsCollection;
use TransilienService\TransilienControllerBundle\Collections\TrainStopCollection;
use TransilienService\TransilienControllerBundle\Entities\Station;
use TransilienService\TransilienControllerBundle\Entities\TrainStop;
use TransilienService\TransilienControllerBundle\Exceptions\InvalidReturnedDataException;

class TransilienDAO
{

    private $curlWrapper = null;
    private $curlWrapperFactory = null;


    public $emptyPostData = array(
        "listOfMap" => null,
        "map"       => null,
        "headers"   => null,
        "list"      => null,
        "target"    => "",
        "serial"    => 0
    );

    protected $url = 'http://ods.ocito.com/ods/transilien/android';

    private $headers = array(
        'Accept: application/json',
        'Content-Type: application/json',
        'ods-modele-mobile: GT-I9100',
        'ods-mobile-id: 87876d7874687FG',
        'ods-os-name-mobile: 10',
        'ods-os-version-mobile: 2.3.4'
    );

    public $targets = array(
        "getAllGares"     => "/transilien/getAllGares", //Get all gare list updated
        "getNextTrains"   => "/transilien/getNextTrains", //Get nextTrains depending of the post data
        "getTrainDetails" => "/transilien/getTrainDetails"
    );

    private $cookieJar = null;
    private $serial = 42;
    protected $userAgent
        = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11';


    /**
     * @var StationsCollection null
     */
    private $stationsCollection = null;


    /**
     * @param CurlAdapterFactory $curlWrapperFactory The adapter factory to use curl properly
     */
    public function __construct(CurlAdapterFactory $curlWrapperFactory)
    {
        $this->curlWrapperFactory = $curlWrapperFactory;
    }

    /**
     * Get all station from WS
     */
    public function getStations()
    {
        //If the collection is already instantiate, the call was already done
        if ($this->stationsCollection instanceof StationsCollection) {
            return $this->stationsCollection;
        } else {

            $stationsCollection = new StationsCollection();

            $post        = array();
            $post['map'] = array("lastUpdate" => "200001010000");
            $raw         = $this->curlThis('getAllGares', $post);

            if (isset($raw[0]['data'])) {
                foreach ($raw[0]['data'] as $rawStation) {
                    $Station       = new Station();
                    $Station->code = $rawStation['codeTR3A'];
                    $Station->id   = $rawStation['codeTR3A'];
                    $Station->name = $rawStation['name'];
                    //$Station->location = $rawStation['positions'];
                    $stationsCollection[] = $Station;
                }
            }
            $this->stationsCollection = $stationsCollection;
            return $this->stationsCollection;
        }
    }

    /**
     * @param string $stationId  The id/code of the station to fetch data from
     * @return StationsCollection
     */
    public function getStation($stationId)
    {
        $stationsCollection = $this->getStations();
        $station            = $stationsCollection->findBy('code', $stationId);

        return $station;
    }

    /**
     * @param Station $from The station to get nextTrains to
     * @param Station $to The station to use as destination to get train of
     * @return TrainStopCollection
     */
    public function nextTrains(Station $from, station $to = null)
    {
        //No cache for this method !
        $trainStopCollection = new TrainStopCollection();

        $codeFrom = $from->code;
        $codeTo   = $to->code;


        $post     = array();
        if ($codeTo !== null) {
            $post['map'] = array('codeDepart' => $codeFrom, 'codeArrivee' => $codeTo);
        } else {
            $post['map'] = array('codeDepart' => $codeFrom);
        }
        $rawTrainsStops = $this->curlThis('getNextTrains', $post);
        if (isset($rawTrainsStops[0]['data'])) {
            foreach ($rawTrainsStops[0]['data'] as $rawTrain) {
                $trainStop = new TrainStop();

                //Yes, this is to convert the frenchy sncf non standard fucking dateTime to a standard !!!
                $dateTimeSplit = explode(' ', $rawTrain["trainHour"]);
                $dateSplit     = explode('/', $dateTimeSplit[0]);
                $time          = $dateTimeSplit[1];
                $dateTime      = $dateSplit[1] . '/' . $dateSplit[0] . '/' . $dateSplit[2] . ' ' . $time;
                $date          = new \DateTime($dateTime);


                $trainStop->type            = $rawTrain['type'];
                $trainStop->dock            = $rawTrain['trainDock'];
                $trainStop->lane            = $rawTrain['trainLane'];
                $trainStop->mention         = $rawTrain['trainMention'];
                $trainStop->mission         = $rawTrain['trainMissionCode'];
                $trainStop->number          = $rawTrain['trainNumber'];
                $trainStop->stopDate        = $date;
                $trainStop->terminusCode    = $rawTrain['trainTerminus'];
                $trainStop->terminusStation = $this->getStation($rawTrain['trainTerminus']);
                $trainStopCollection[0]      = $trainStop;
            }
        }
        return $trainStopCollection;
    }


    /**
     * @param       $target   string  the target string to execute. the real value is taken in $this->targets
     * @param null  $postData data to pass into the post payload. automatically json_encoded merged with
     *              defaults in $this->postData
     * @param array $headers  all headers send to the servers. merged with defaults in $this->headers
     *
     * @throws InvalidReturnedDataException If the server return something nasty of not valid json
     * @return mixed the result of the query. if json, its decoded
     */
    protected function curlThis(
        $target,
        $postData = null /* array */,
        $headers = array()
    ) {
        $curl = $this->curlWrapperFactory->createCurl($this->url);

        if ($this->cookieJar == null) {
            $curl->setOpt(CURLOPT_COOKIEJAR, $this->createCookieJar());
        } else {
            $curl->setOpt(CURLOPT_COOKIEFILE, $this->cookieJar);
        }
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_HEADER, false);
        $curl->setOpt(CURLOPT_USERAGENT, $this->userAgent);
        $curl->setOpt(CURLOPT_AUTOREFERER, true);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $curl->setOpt(
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-Type: application/json',
                'ods-modele-mobile: GT-I9100',
                'ods-mobile-id: 87876d7874687FG',
                'ods-os-name-mobile: 10',
                'ods-os-version-mobile: 2.3.4'
            )
        );


        if ($postData !== null) {
            $postData           = array_merge($this->emptyPostData, $postData);
            $postData['target'] = $this->targets[$target];
            $postData['serial'] = $this->serial++;
            $postData           = json_encode(array($postData));
            //$postData = str_replace('\\','',$postData);
            //echo $postData ;
            $curl->setOpt(CURLOPT_POSTFIELDS, $postData);
        }

        $result = $curl->exec();
        if (strpos($result, '{') !== false) {
            $result = json_decode($result, true);
        } else {
            throw new InvalidReturnedDataException('The server respond with a big crap instead of data');
        }

        return $result;
    }

    /**
     * create a temp file used for storing cookies
     * @return string the path to the cookieJar created in a temp place
     */
    private function createCookieJar()
    {
        return $this->cookieJar = tempnam("stif", "CURLCOOKIE");
    }
}
