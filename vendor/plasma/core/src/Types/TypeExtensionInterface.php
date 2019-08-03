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
 * A Type Extension is used to map SQL values to and from PHP values.
 * A typical type extension is registered globally with the `TypeExtensionManager`.
 * The type conversion is always invoked by the driver and
 * yields from and to SQL types the equivalent conversions.
 */
interface TypeExtensionInterface {
    /**
     * Whether the type extension can handle the conversion of the passed value.
     * Before this method is used, the common types are checked first.
     * `class` -> `interface` -> `type` -> this.
     * @param mixed                                   $value
     * @param \Plasma\ColumnDefinitionInterface|null  $column
     * @return bool
     */
    function canHandleType($value, ?\Plasma\ColumnDefinitionInterface $column): bool;
    
    /**
     * Get the human-readable type this Type Extension is for.
     * @return string  E.g. `BIGINT`, `VARCHAR`, etc.
     */
    function getHumanType(): string;
    
    /**
     * Encodes a PHP value into a binary SQL value.
     * @param mixed                              $value   The value to encode.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @return \Plasma\Types\TypeExtensionResultInterface
     */
    function encode($value, \Plasma\ColumnDefinitionInterface $column): \Plasma\Types\TypeExtensionResultInterface;
    
    /**
     * Decodes a binary SQL value into a PHP value.
     * @param mixed  $value  The encoded binary. Actual type depends on the driver.
     * @return \Plasma\Types\TypeExtensionResultInterface
     */
    function decode($value): \Plasma\Types\TypeExtensionResultInterface;
}
