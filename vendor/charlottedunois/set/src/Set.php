<?php
/**
 * Set
 * Copyright 2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Set/blob/master/LICENSE
*/

namespace CharlotteDunois\Collect;

/**
 * Set, an util to conventionally store unique values.
 */
class Set implements \Countable, \Iterator {
    /**
     * @var array
     */
    protected $data = array();
    
    /**
     * I think you are supposed to know what this does.
     * @param array|null $data
     */
    function __construct(array $data = null) {
        if(!empty($data)) {
            foreach($data as $value) {
                $this->add($value);
            }
        }
    }
    
    /**
     * @internal
     */
    function __debugInfo() {
        return $this->data;
    }
    
    /**
     * Returns the current element.
     * @return mixed
     */
    function current() {
        return \current($this->data);
    }
    
    /**
     * Fetch the key from the current element.
     * @return null
     */
    function key() {
        return null;
    }
    
    /**
     * Advances the internal pointer.
     * @return mixed|false
     */
    function next() {
        return \next($this->data);
    }
    
    /**
     * Resets the internal pointer.
     */
    function rewind() {
        return \reset($this->data);
    }
    
    /**
     * Checks if current position is valid.
     * @return bool
     */
    function valid() {
        return (\key($this->data) !== null);
    }
    
    /**
     * Returns the amount of data in the Set.
     * @return int
     */
    function count() {
        return \count($this->data);
    }
    
    /**
     * Returns all items.
     * @return mixed[]
     */
    function all() {
        return \array_values($this->data);
    }
    
    /**
     * Checks if a given value is in the Set.
     * @param mixed  $value
     * @return bool
     */
    function has($value) {
        return \in_array($value, $this->data, true);
    }
    
    /**
     * Adds a value to the Set.
     * @param mixed  $value
     * @return $this
     */
    function add($value) {
        if(!\in_array($value, $this->data, true)) {
            $this->data[] = $value;
        }
        
        return $this;
    }
    
    /**
     * Deletes a value from the Set.
     * @param mixed  $value
     * @return $this
     */
    function delete($value) {
        $key = \array_search($value, $this->data, true);
        if($key !== false) {
            unset($this->data[$key]);
        }
        
        return $this;
    }
    
    /**
     * Clears the Set.
     * @return $this
     */
    function clear() {
        $this->data = array();
        return $this;
    }
}
