<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

namespace CharlotteDunois\Livia\Utils;

/**
 * Provides a simple interface to a timer.
 */
class HRTimer {
    /**
     * Whether the extension hrtime is installed and loaded.
     * @var bool
     */
    protected $hrtime;
    
    /**
     * Whether we use a PHP version which has the function hrtime. (PHP >= 7.3.0)
     * @var bool
     */
    protected $nativeHrtime;
    
    /**
     * The timer.
     * @var \HRTime\StopWatch|int|float
     */
    protected $timer;
    
    /**
     * The last time we called time.
     * @var int|float
     */
    protected $lastTime;
    
    /**
     * Constructor.
     */
    function __construct() {
        $this->hrtime = \extension_loaded('hrtime');
        $this->nativeHrtime = \function_exists('hrtime');
        
        if($this->hrtime && !$this->nativeHrtime) {
            $this->timer = new \HRTime\StopWatch();
        }
    }
    
    /**
     * Returns the resolution (the end product of 10^X, positive). Nano for hrtime (native and pecl), micro for fallback.
     * @return int
     */
    function getResolution(): int {
        return ($this->nativeHrtime || $this->hrtime ? 1000000000 : 1000000);
    }
    
    /**
     * Starts the timer.
     * @return void
     */
    function start(): void {
        if($this->nativeHrtime) {
            $this->timer = \hrtime(true);
        } elseif($this->hrtime) {
            $this->timer->start();
        } else {
            $this->timer = \microtime(true);
        }
    }
    
    /**
     * Stops the timer and returns the elapsed time, in nanoseconds.
     * @return int
     */
    function stop(): int {
        if($this->timer === null) {
            return 0;
        }
        
        if($this->nativeHrtime) {
            $elapsed = (int) (\hrtime(true) - $this->timer);
        } elseif($this->hrtime) {
            $this->timer->stop();
            $elapsed = (int) $this->timer->getElapsedTime(\HRTime\Unit::NANOSECOND);
        } else {
            $elapsed = \microtime(true) - $this->timer;
            $elapsed = \ceil(($elapsed * 1000000000));
        }
        
        return ((int) $elapsed);
    }
    
    /**
     * Returns the elapsed time since the last `time` call, in nanoseconds.
     * @return int
     */
    function time(): int {
        if($this->nativeHrtime) {
            if(!$this->lastTime) {
                $this->lastTime = $this->timer;
            }
            
            $time = \hrtime(true);
            $elapsed = (int) ($time - $this->lastTime);
            $this->lastTime = $time;
        } elseif($this->hrtime) {
            $this->timer->stop();
            $elapsed = (int) $this->timer->getLastElapsedTime(\HRTime\Unit::NANOSECOND);
            $this->timer->start();
        } else {
            if(!$this->lastTime) {
                $this->lastTime = $this->timer;
            }
            
            $time = \microtime(true);
            $elapsed = $time - $this->lastTime;
            
            $this->lastTime = $time;
            $elapsed = \ceil(($elapsed * 1000000000));
        }
        
        return ((int) $elapsed);
    }
}
