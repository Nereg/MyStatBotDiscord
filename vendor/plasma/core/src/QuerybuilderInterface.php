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
 * Represents a querybuilder.
 *
 * A querybuilder can be used to also provide drivers for NoSQL DBMS.
 * Through the querybuilder interface a driver can implement a querybuilder
 * to use through the generic Plasma client interface.
 */
interface QuerybuilderInterface {
    /**
     * Creates a new instance of the querybuilder.
     * @return self
     */
    static function create(): self;
    
    /**
     * Returns the query.
     * @return mixed  This may be a string for SQL.
     */
    function getQuery();
    
    /**
     * Returns the associated parameters for the query.
     * @return array
     */
    function getParameters(): array;
}
