<?php

namespace TransilienService\TransilienControllerBundle\Collections;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use TransilienService\TransilienControllerBundle\Entities\EntityInterface;

abstract class CollectionAbstract implements CollectionInterface, \ArrayAccess
{

    protected $collectionData = array();
    protected $collectionIndexes = array();

    /**Should return if the item passed is accepted
     * Item cannot be scalar
     * @param mixed $item the item to test
     * @return bool
     */
    abstract protected function checkType(EntityInterface $item);

    /**
     * find an item by it's index. Index if deviated from the fields names of the items
     * @param $field
     * @param $value
     * @return null
     */
    public function findBy($field, $value)
    {
        if (isset($this->collectionIndexes[$field]) && isset($this->collectionIndexes[$field][$value])) {
            return $this->collectionIndexes[$field][$value][1];
        }
        return null;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->collectionData[$offset]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->collectionData[$offset]['data'];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @throws InvalidParameterException if item is not the good type for the current collection
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (($offset !== null && empty($offset)) || $offset == null) {
            $offset = count($this->collectionData);
        }
        if (!$this->checkType($value)) {
            throw new InvalidParameterException('The item cannot be of the type "' . get_class($value) . '"');
        }
        $indexes                       = $this->populateIndexes($value, $offset);
        $this->collectionData[$offset] = array('data' => $value, 'indexes' => $indexes);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        $indexes = $this->collectionData[$offset]['indexes'];
        $this->forgetMe($indexes, $offset);
        unset($this->collectionData[$offset]);
    }

    /**
     * Create indexes for each parameters of the item to find it by any of them. (scalar values only)
     * @param $item
     * @param $offset
     * @return array
     */
    private function populateIndexes($item, $offset)
    {

        $indexes = array();
        foreach ($item as $key => $value) {
            if ((gettype($key) == 'integer' || gettype($key) == 'string')
                && (gettype($value) == 'integer' || gettype($value) == 'string')
            ) {
                if (!isset($this->collectionIndexes[$key])) {
                    $this->collectionIndexes[$key] = array();
                }
                $this->collectionIndexes[$key][$value] = array($offset, $item);
                $indexes[]                             = array($key, $value);
            }
        }
        return $indexes;
    }


    /**
     * remote an item from indexes
     * @param array $indexes
     * @param $offset
     */
    private function forgetMe(array $indexes, $offset)
    {
        foreach ($indexes as $key => $value) {
            if (isset($this->collectionIndexes[$key]) && isset($this->collectionIndexes[$key][$value])) {
                unset($this->collectionIndexes[$key][$value]);
            }
        }
    }
}
