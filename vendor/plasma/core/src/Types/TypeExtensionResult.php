<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Types;

/**
 * Represents a successful encoding conversion.
 */
class TypeExtensionResult implements TypeExtensionResultInterface {
    /**
     * @var mixed
     */
    protected $type;
    
    /**
     * @var bool
     */
    protected $unsigned;
    
    /**
     * @var mixed
     */
    protected $value;
    
    /**
     * Constructor.
     * @param mixed  $type
     * @param bool   $unsigned
     * @param mixed  $value
     */
    function __construct($type, bool $unsigned, $value) {
        $this->type = $type;
        $this->unsigned = $unsigned;
        $this->value = $value;
    }
    
    /**
     * Get the SQL type.
     * @return mixed  Driver-dependent.
     */
    function getSQLType() {
        return $this->type;
    }
    
    /**
     * Get the encoded value.
     * @return bool
     */
    function isUnsigned(): bool {
        return $this->unsigned;
    }
    
    /**
     * Get the encoded value.
     * @return mixed
     */
    function getValue() {
        return $this->value;
    }
}
