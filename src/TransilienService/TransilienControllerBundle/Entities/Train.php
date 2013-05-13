<?php

namespace TransilienService\TransilienControllerBundle\Entities;

class Train implements EntityInterface
{
    public $number;
    public $mission;
    public $type;
    public $terminusCode ;
    /**
     * @var Station
     */
    public $terminusStation ;
}
