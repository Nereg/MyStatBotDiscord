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
 * An abstract type extension.
 */
abstract class AbstractTypeExtension implements TypeExtensionInterface {
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var mixed
     */
    protected $sqlType;
    
    /**
     * @var callable
     */
    protected $filter;
    
    /**
     * Constructor.
     * @param string    $type
     * @param mixed     $sqlType
     * @param callable  $filter
     */
    function __construct(string $type, $sqlType, callable $filter) {
        $this->type = $type;
        $this->sqlType = $sqlType;
        $this->filter = $filter;
    }
    
    /**
     * Whether the type extension can handle the conversion of the passed value.
     * Before this method is used, the common types are checked first.
     * `class` -> `interface` -> `type` -> this.
     * @param mixed                                   $value
     * @param \Plasma\ColumnDefinitionInterface|null  $column
     * @return bool
     */
    function canHandleType($value, ?\Plasma\ColumnDefinitionInterface $column): bool {
        $cb = $this->filter;
        return $cb($value, $column);
    }
    
    /**
     * Get the human-readable type this Type Extension is for.
     * @return string  E.g. `BIGINT`, `VARCHAR`, etc.
     */
    function getHumanType(): string {
        return $this->type;
    }
    
    /**
     * Get the SQL type this Type Extension is for.
     * @return mixed
     */
    function getSQLType() {
        return $this->sqlType;
    }
    
    /**
     * Encodes a PHP value into a binary SQL value.
     * @param mixed                              $value   The value to encode.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @return \Plasma\Types\TypeExtensionResultInterface
     */
    abstract function encode($value, \Plasma\ColumnDefinitionInterface $column): \Plasma\Types\TypeExtensionResultInterface;
    
    /**
     * Decodes a binary SQL value into a PHP value.
     * @param mixed  $value  The encoded binary. Actual type depends on the driver.
     * @return \Plasma\Types\TypeExtensionResultInterface
     */
    abstract function decode($value): \Plasma\Types\TypeExtensionResultInterface;
}
