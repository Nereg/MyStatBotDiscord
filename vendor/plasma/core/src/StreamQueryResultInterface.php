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
 * This is the more advanced query result interface, which is a readable stream.
 * That means, for `SELECT` statements a `data` event will be emitted for each row.
 * At the end of a query, a `end` event will be emitted to notify of the completion.
 */
interface StreamQueryResultInterface extends \React\Stream\ReadableStreamInterface, QueryResultInterface {
    /**
     * Buffers all rows and returns a promise which resolves with an instance of `QueryResultInterface`.
     * This method does not guarantee that all rows get returned, as the buffering depends on when this
     * method gets invoked. As such implementations may buffer rows directly from the start to ensure
     * all rows get returned. But users must not assume this behaviour is the case.
     * @return \React\Promise\PromiseInterface
     */
    function all(): \React\Promise\PromiseInterface;
}
