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
 * This is just a basic interface. There is an additional interface which defines that the query result is stream-based.
 * A driver might implement a query result based on ReactiveX instead, which will be documented as such.
 */
interface QueryResultInterface {
    /**
     * Get the number of affected rows (for UPDATE, DELETE, etc.).
     * @return int
     */
    function getAffectedRows(): int;
    
    /**
     * Get the number of warnings sent by the server.
     * @return int
     */
    function getWarningsCount(): int;
    
    /**
     * Get the used insert ID for the row, if any. `INSERT` statements only.
     * @return int|null
     */
    function getInsertID(): ?int;
    
    /**
     * Get the field definitions, if any. `SELECT` statements only.
     * @return \Plasma\ColumnDefinitionInterface[]|null
     */
    function getFieldDefinitions(): ?array;
    
    /**
     * Get the rows, if any. `SELECT` statements only.
     * @return array|null
     */
    function getRows(): ?array;
}
