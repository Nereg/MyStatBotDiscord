<?php
/**
 * Plasma Core component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma;

/**
 * This interface defines a common column definition abstraction.
 */
interface ColumnDefinitionInterface {
    /**
     * Get the database name this column is in.
     * @return string
     */
    function getDatabaseName(): string;
    
    /**
     * Get the table name this column is in.
     * @return string
     */
    function getTableName(): string;
    
    /**
     * Get the column name.
     * @return string
     */
    function getName(): string;
    
    /**
     * Get the type name, such as `BIGINT`, `VARCHAR`, etc.
     * @return string
     */
    function getType(): string;
    
    /**
     * Get the charset, such as `utf8mb4`.
     * @return string
     */
    function getCharset(): string;
    
    /**
     * Get the maximum field length, if any.
     * @return int|null
     */
    function getLength(): ?int;
    
    /**
     * Whether the column is nullable (not `NOT NULL`).
     * @return bool
     */
    function isNullable(): bool;
    
    /**
     * Whether the column is auto incremented.
     * @return bool
     */
    function isAutoIncrement(): bool;
    
    /**
     * Whether the column is the primary key.
     * @return bool
     */
    function isPrimaryKey(): bool;
    
    /**
     * Whether the column is the unique key.
     * @return bool
     */
    function isUniqueKey(): bool;
    
    /**
     * Whether the column is part of a multiple/composite key.
     * @return bool
     */
    function isMultipleKey(): bool;
    
    /**
     * Whether the column is unsigned (only makes sense for numeric types).
     * @return bool
     */
    function isUnsigned(): bool;
    
    /**
     * Whether the column gets zerofilled to the length.
     * @return bool
     */
    function isZerofilled(): bool;
    
    /**
     * Get the column flags.
     * @return int
     */
    function getFlags(): int;
    
    /**
     * Get the maximum shown decimal digits.
     * @return int|null
     */
    function getDecimals(): ?int;
    
    /**
     * Parses the row value into the field type.
     * @param mixed  $value
     * @return mixed
     */
    function parseValue($value);
}
