<?php

namespace TransilienService\TransilienControllerBundle\Controller ;

use TransilienService\TransilienControllerBundle\Services\TransilienService;

class TransilienServiceController {


    /**
     * @var TransilienService
     */
    private $TransilienService ;


    public function __construct(TransilienService $TransilienService){
        $this->TransilienService = $TransilienService;
    }
}