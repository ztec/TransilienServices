<?php
namespace TransilienService\TransilienControllerBundle\Collections;

use TransilienService\TransilienControllerBundle\Entities\EntityInterface;
use TransilienService\TransilienControllerBundle\Entities\TrainStop;

class TrainStopCollection extends CollectionAbstract
{
    /**
     * Should return true if the type of the item to store is handled by this collection
     * @param EntityInterface $station
     * @return bool
     */
    protected function checkType(EntityInterface $station)
    {
        if ($station instanceof TrainStop) {
            return true;
        } else {
            return false;
        }
    }
}
