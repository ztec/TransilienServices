<?php

namespace TransilienService\Adapters\Curl ;

class CurlAdapterFactory
{
    /**
     * @params string $url the url to mee the query to
     * @return Curl
     */
    public function createCurl($url)
    {
        return new Curl($url);
    }
}
