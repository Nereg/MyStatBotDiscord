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
 * A driver factory is used to create new driver instances. The factory is responsible to create the drivers with the necessary arguments.
 */
interface DriverFactoryInterface {
    /**
     * Creates a new driver instance.
     * @return \Plasma\DriverInterface
     */
    function createDriver(): \Plasma\DriverInterface;
}
