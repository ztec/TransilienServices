<?php

namespace TransilienService\TransilienControllerBundle\Collections;


use TransilienService\TransilienControllerBundle\Entities\EntityInterface;
use TransilienService\TransilienControllerBundle\Entities\Station;

class StationsCollection extends CollectionAbstract
{

    /**
     * Should return the type of the item to store
     * @param EntityInterface $station
     * @return bool
     */
    protected function checkType(EntityInterface $station)
    {
        if ($station instanceof Station) {
            return true;
        } else {
            return false;
        }
    }
}
