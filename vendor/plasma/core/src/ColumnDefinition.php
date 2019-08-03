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
 * Column Definitions define columns (who would've thought of that?). Such as their name, type, length, etc.
 */
abstract class ColumnDefinition implements ColumnDefinitionInterface {
    /**
     * @var string
     */
    protected $database;
    
    /**
     * @var string
     */
    protected $table;
    
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var string
     */
    protected $charset;
    
    /**
     * @var int|null
     */
    protected $length;
    
    /**
     * @var int
     */
    protected $flags;
    
    /**
     * @var int|null
     */
    protected $decimals;
    
    /**
     * Constructor.
     * @param string    $database
     * @param string    $table
     * @param string    $name
     * @param string    $type
     * @param string    $charset
     * @param int|null  $length
     * @param int       $flags
     * @param int|null  $decimals
     */
    function __construct(string $database, string $table, string $name, string $type, string $charset, ?int $length, int $flags, ?int $decimals) {
        $this->database = $database;
        $this->table = $table;
        $this->name = $name;
        $this->type = $type;
        $this->charset = $charset;
        $this->length = $length;
        $this->flags = $flags;
        $this->decimals = $decimals;
    }
    
    /**
     * Get the database name this column is in.
     * @return string
     */
    function getDatabaseName(): string {
        return $this->database;
    }
    
    /**
     * Get the table name this column is in.
     * @return string
     */
    function getTableName(): string {
        return $this->table;
    }
    
    /**
     * Get the column name.
     * @return string
     */
    function getName(): string {
        return $this->name;
    }
    
    /**
     * Get the type name, such as `BIGINT`, `VARCHAR`, etc.
     * @return string
     */
    function getType(): string {
        return $this->type;
    }
    
    /**
     * Get the charset, such as `utf8mb4`.
     * @return string
     */
    function getCharset(): string {
        return $this->charset;
    }
    
    /**
     * Get the maximum field length, if any.
     * @return int|null
     */
    function getLength(): ?int {
        return $this->length;
    }
    
    /**
     * Get the column flags.
     * @return int
     */
    function getFlags(): int {
        return $this->flags;
    }
    
    /**
     * Get the maximum shown decimal digits.
     * @return int|null
     */
    function getDecimals(): ?int {
        return $this->decimals;
    }
    
    /**
     * Parses the row value into the field type.
     * @param mixed  $value
     * @return mixed
     */
    function parseValue($value) {
        try {
            return \Plasma\Types\TypeExtensionsManager::getManager()->decodeType($this->type, $value)->getValue();
        } catch (\Plasma\Exception $e) {
            /* Continue regardless of error */
        }
        
        return $value;
    }
}
