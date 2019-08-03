<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Arguments;

/**
 * A fancy bag for our arguments. This class is used for a library-internal optimization.
 */
class ArgumentBag {
    /**
     * The argument this bag is for.
     * @var \CharlotteDunois\Livia\Arguments\Argument
     */
    public $argument;
    
    /**
     * The values.
     * @var mixed[]
     */
    public $values = array();
    
    /**
     * Maximum number of times to prompt for the argument.
     * @var int|float
     */
    public $promptLimit;
    
    /**
     * The prompt messages.
     * @var \CharlotteDunois\Yasmin\Models\Message[]
     */
    public $prompts = array();
    
    /**
     * The answer messages.
     * @var \CharlotteDunois\Yasmin\Models\Message[]
     */
    public $answers = array();
    
    /**
     * Whether the bag got cancelled.
     * @var string|null
     */
    public $cancelled;
    
    /**
     * Whether we are done.
     * @var bool
     */
    public $done = false;
    
    /**
     * Constructor.
     * @param \CharlotteDunois\Livia\Arguments\Argument  $argument
     * @param int|float                                  $promptLimit
     * @internal
     */
    function __construct(\CharlotteDunois\Livia\Arguments\Argument $argument, $promptLimit = \INF) {
        $this->argument = $argument;
        $this->promptLimit = $promptLimit;
    }
    
    /**
     * Closes the bag formally and returns itself.
     * @return $this
     */
    function done() {
        $this->done = true;
        return $this;
    }
}
