<?php

namespace TransilienService\TransilienControllerBundle\DAO;

class StifModel
{

    public $emptyPostData
        = array("listOfMap" => null, "map" => null, "headers" => null, "list" => null, "target" => "", "serial" => 0);

    public $headers
        = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'ods-modele-mobile: GT-I9100',
            'ods-mobile-id: 87876d7874687FG',
            'ods-os-name-mobile: 10',
            'ods-os-version-mobile: 2.3.4'
        );

    protected $url = 'http://ods.ocito.com/ods/transilien/android';

    public $targets
        = array(
            "getAllGares"     => "/transilien/getAllGares", //Get all gare list updated
            "getNextTrains"   => "/transilien/getNextTrains", //Get nextTrains depending of the post data
            "getTrainDetails" => "/transilien/getTrainDetails",
            "test"            => "/transilien/getGareDetails"
        );

    private $cookieJar = null;
    protected $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11';

    public $serial = 0;


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function getAllStations()
    {
        //$this->load->driver('cache', array('adapter' => 'file'));


        $stations       = null;
        $stationsByTR3  = null;
        $stationsByName = null;
        $stationsByUIC  = null;

        if (!$stations || !$stationsByName || !$stationsByTR3) {
            $post        = array();
            $post['map'] = array("lastUpdate" => "200001010000");
            $garesRaw    = $this->curlThis('getAllGares', $post);
            $gareRaw2    = $this->callCurl('http://sncf.mobi/infotrafic/iphoneapp/gares/index/lastUpdate/0?');
            $gareRaw2    = json_decode($gareRaw2, true);

            $gare2byCode = array();
            if (isset($gareRaw2['stations'])) {
                $gare2 = true;
                foreach ($gareRaw2['stations'] as $station) {
                    $gare2byCode[$station['codeDDG']] = $station;
                }
            } else {
                $gare2 = false;
            }
            $stations       = array();
            $stationsByTR3  = array();
            $stationsByName = array();
            $stationsByUIC  = array();
            if (isset($garesRaw[0]['data'])) {
                foreach ($garesRaw[0]['data'] as $gare) {
                    $gr = array(
                        'code'     => $gare['codeTR3A'],
                        'id'       => $gare['codeTR3A'],
                        'name'     => $gare['name'],
                        'location' => $gare['positions']
                    );
                    if ($gare2 && isset($gare2byCode[$gare['codeTR3A']])) {
                        //$gr['qlt'] = $gare2byCode[$gare['codeTR3A']]['codeQLT'];
                        $gr['uic'] = $gare2byCode[$gare['codeTR3A']]['codeUIC'];
                        //$gr['type'] = $gare2byCode[$gare['codeTR3A']]['stationType'];
                        //$gr['cat'] = $gare2byCode[$gare['codeTR3A']]['stationCat'];

                        $stationsByUIC[$gr['uic']] = $gr;
                    }
                    $stations[]                  = $gr;
                    $stationsByName[$gr['name']] = $gr;
                    $stationsByTR3[$gr['code']]  = $gr;

                }
                /*RayCache::write('stations', $stations);
                RayCache::write('stationsByTR3', $stationsByTR3);
                RayCache::write('stationsByName', $stationsByName);
                RayCache::write('stationsByUic', $stationsByUIC);*/
            }
        }

        return array(
            'stations'       => $stations,
            'stationsByTR3'  => $stationsByTR3,
            'stationsByName' => $stationsByName,
            'stationsByUic'  => $stationsByUIC
        );
    }


    public function isReachable($stationDepart, $stationArrivee)
    {

        $result = null;
        if (!$result) {
            $this->callCurl('http://transilien.mobi/');
            $this->callCurl(
                'http://transilien.mobi/TempReelSaisieSubmit.do?etapeDepart=0&debutDepart=' . $stationDepart['name'] . '&debutArrivee='
                    . $stationArrivee['name']
            );
            $result = $this->callCurl(
                'http://transilien.mobi/TempReelSaisieSubmit.do?etapeDepart=1&etapeArrivee=1&codeGareDepart=' . $stationDepart['code']
                    . '&codeGareArrivee=' . $stationArrivee['code']
            );

            if (strpos($result, 'ne dessert la gare') === false) {
                $data   = 'true';
                $return = true;
            } else {
                $data   = 'false';
                $return = false;
            }
            /*$cache->write($stationDepart['code'] . $stationArrivee['code'], $data);*/
        } else {
            if ($result == 'true') {
                $return = true;
            } else {
                $return = false;
            }
        }

        return $return;
    }

    protected $cookieJarMob = null;

    public function callCurl($url)
    {
        $curlSession = curl_init();
        if ($this->cookieJarMob == null) {
            curl_setopt($curlSession, CURLOPT_COOKIEJAR, $this->createCookieJarMob());
            //curl_setopt( $curlSession, CURLOPT_COOKIEFILE, $this->cookieJarMob );
        } else {
            //curl_setopt( $curlSession, CURLOPT_COOKIEJAR, $this->cookieJarMob );
            curl_setopt($curlSession, CURLOPT_COOKIEFILE, $this->cookieJarMob);
        }

        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlSession, CURLOPT_HEADER, false);
        curl_setopt(
            $curlSession,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1'
        );
        curl_setopt($curlSession, CURLOPT_AUTOREFERER, true);

        curl_setopt(
            $curlSession,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
                'Accept-Encoding: gzip,deflate,sdch',
                'Accept-Language: fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4'
            )
        );
        //If location are sent, curl will follow them
        curl_setopt($curlSession, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($curlSession, CURLOPT_URL, $url);
        $result = curl_exec($curlSession);

        //curl_close( $ch );
        return $result;
    }

    public function getTrainDetails($idTrain)
    {
        $post        = array();
        $post['map'] = array("trainNumber" => $idTrain, "theoric" => false);


        $trainStations = null;
        if (!$trainStations) {
            $garesRaw = $this->curlThis('getTrainDetails', $post);
            $garesRaw = $garesRaw[0];
            if ($garesRaw['state'] == 200) {
                $trainStations = $garesRaw['data'];
            }
            /*RayCache::write('stations' . $idTrain, $trainStations);*/
        }

        return $trainStations;
    }

    public function getStationById($id)
    {
        $data = $this->getAllStations();
        if (isset($data['stationsByTR3'][$id])) {
            return $data['stationsByTR3'][$id];
        } else {
            if (isset($data['stationsByUic'][$id])) {
                return $data['stationsByUic'][$id];
            } else {
                return null;
            }
        }
    }

    public function getStationByString($search)
    {
        $search = strtolower($search);
        RayCache::getInstance(
            null,
            null,
            array('path' => 'cache/', 'prefix' => 'stif_stations', 'expire' => '+604800 seconds')
        );
        $resultsStation = null;
        if (is_array($resultsStation)) {
            return $resultsStation;
        }
        $stations       = $this->getAllStations();
        $resultsStation = array();

        foreach ($stations['stations'] as $station) {
            if ((strpos(strtolower($station['name']), $search) !== false)
                || (strtolower($station['code']) == $search)
                || (strpos(strtolower($station['name']), $search) !== false)
            ) {
                $resultsStation[] = $station;
            }
        }

        /* RayCache::write('search' . md5($search), $resultsStation);*/

        return $resultsStation;
    }

    public function getNextTrains($from, $to = null)
    {
        $from = strtoupper($from);
        if ($to !== null) {
            $to = strtoupper($to);
        }
        /*RayCache::getInstance(
            NULL,
            NULL,
            array('path' => 'cache/', 'prefix' => 'stif_trains_', 'expire' => '+5 seconds', 'markAsPending' => TRUE,)
        );*/

        $nextTrains = null;
        if (is_array($nextTrains)) {
            return $nextTrains;
        }
        $post = array();
        if ($to !== null) {
            $post['map'] = array('codeDepart' => $from, 'codeArrivee' => $to);
        } else {
            $post['map'] = array('codeDepart' => $from);
        }
        $trains     = $this->curlThis('getNextTrains', $post);
        $nextTrains = array();
        if (isset($trains[0]['data'])) {
            foreach ($trains[0]['data'] as $train) {
                $tr                      = $train;
                $terminus                = $this->getStationById($train['trainTerminus']);
                $tr['trainTerminusName'] = $terminus['name'];
                $tr['id']                = $tr['trainNumber'];

                $dateTimeSplit = explode(' ', $tr["trainHour"]);
                $dateSplit     = explode('/', $dateTimeSplit[0]);
                $time          = $dateTimeSplit[1];
                $dateTime      = $dateSplit[1] . '/' . $dateSplit[0] . '/' . $dateSplit[2] . ' ' . $time;
                $date          = new \DateTime($dateTime);

                $tr['trainDate'] = $date->format('c');
                //$tr['trainMention'] = 'Retardé';

                if (isset($tr['dessertes'])) {
                    $dessertes = array();

                    foreach ($tr['dessertes'] as $des) {
                        $a = $this->getStationById($des);
                        unset($a['location']);
                        //$a['url'] = 'https://www.riper.fr/api/stif/stations/'.$a['id'] ;
                        $dessertes[] = $a;
                    }
                    $tr['dessertes'] = $dessertes;
                }

                $nextTrains[] = $tr;
            }
        }

        /*RayCache::write(var_export($from, 1) . var_export($to, 1), $nextTrains);*/

        return $nextTrains;
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @param       $target   string  the target string to execute. the real value is taken in $this->targets
     * @param null  $postData data to pass into the post payload. automatically json_encoded merged with defaults in $this->postData
     * @param array $headers  all headers send to the servers. merged with defaults in $this->headers
     *
     * @return mixed the result of the query. if json, its decoded
     */
    protected function curlThis($target, $postData = null /* array */, $headers = array())
    {
        $ch = curl_init($this->url);
        if ($this->cookieJar == null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->createCookieJar());
        } else {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieJar);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt(
            $ch,
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
        //If location are sent, curl will follow them
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($postData !== null) {
            $postData           = array_merge($this->emptyPostData, $postData);
            $postData['target'] = $this->targets[$target];
            $postData['serial'] = $this->serial++;
            $postData           = json_encode(array($postData));
            //$postData = str_replace('\\','',$postData);
            //echo $postData ;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $result = curl_exec($ch);
        curl_close($ch);
        if (strpos($result, '{') !== false) {
            file_put_contents($target . '.json', $result);
            $result = json_decode($result, true);
        }

        return $result;
    }

    private function createCookieJar()
    {
        return $this->cookieJar = tempnam("stif", "CURLCOOKIE");
    }

    private function createCookieJarMob()
    {
        return $this->cookieJarMob = tempnam("stif", "MOB");
    }
}


//generate test data
$t = new StifModel();

$result = $t->getAllStations();
$result = $t->getNextTrains('ARP', 'BFM');
