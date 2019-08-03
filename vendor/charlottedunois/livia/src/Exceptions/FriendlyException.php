<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Exceptions;

/**
 * Has a message that can be considered user-friendly.
 */
class FriendlyException extends \Exception {
    /**
     * @internal
     */
    function __construct($message) {
        parent::__construct($message);
    }
}
