<?php

namespace TransilienService\Adapters\Curl;

class Curl
{

    private $curl = null;

    /**
     * create curl adapter
     * @param string $url the url to mee the query to
     */
    public function __construct($url)
    {
        $this->curl = curl_init($url);
    }

    /**
     * @param $name string Curl option name
     * @param $value mixed the option value
     */
    public function setOpt($name, $value)
    {
        return curl_setopt($this->curl, $name, $value);
    }

    /**
     * execute the curl query
     * @return mixed
     */
    public function exec()
    {
        return curl_exec($this->curl);
    }


    /**
     * close the curl instance.  The object cannot be used anymore.
     */
    private function close()
    {
        curl_close($this->curl);
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->close();
    }
}
