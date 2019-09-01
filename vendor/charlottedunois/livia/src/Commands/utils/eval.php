<?php
/**
 * Livia
 * Copyright 2017-2019 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Livia/blob/master/LICENSE
*/

return function ($client) {
    return (new class($client) extends \CharlotteDunois\Livia\Commands\Command {
        protected $timeformats = array('ns', 'µs', 'ms');
        protected $lastResult;
        
        /** @var \Symfony\Component\VarDumper\Cloner\VarCloner */
        protected $cloner;
        
        /** @var \Symfony\Component\VarDumper\Dumper\CliDumper */
        protected $dumper;
        
        /** @var bool */
        protected $xdebug;
        
        function __construct(\CharlotteDunois\Livia\Client $client) {
            parent::__construct($client, array(
                'name' => 'eval',
                'aliases' => array(),
                'group' => 'utils',
                'description' => 'Executes PHP code.',
                'guildOnly' => false,
                'ownerOnly' => true,
                'argsSingleQuotes' => false,
                'args' => array(
                    array(
                        'key' => 'script',
                        'prompt' => 'What is the fancy code you wanna run?',
                        'type' => 'string'
                    )
                ),
                'guarded' => true
            ));
            
            $this->cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
            $this->dumper = new \Symfony\Component\VarDumper\Dumper\CliDumper();
            $this->xdebug = \extension_loaded('xdebug');
            
            $this->cloner->setMinDepth(1);
            $this->cloner->setMaxItems(0);
            $this->dumper->setColors(false);
        }
        
        function run(\CharlotteDunois\Livia\Commands\Context $context, \ArrayObject $args, bool $fromPattern) {
            $messages = array();
            $prev = null;
            
            $code = $args['script'];
            if(\mb_substr($code, -1) !== ';') {
                $code .= ';';
            }
            
            if(\mb_strpos($code, 'return') === false && \mb_strpos($code, 'echo') === false) {
                $code = \explode(';', $code);
                $code[(\count($code) - 2)] = \PHP_EOL.'return '.\trim($code[(\count($code) - 2)]);
                $code = \implode(';', $code);
            }
            
            return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use ($context, $args, $code, &$messages, &$prev) {
                $time = null;
                
                $timer = function (bool $callback = false) {
                    static $hrtime;
                    return $this->timer($hrtime, $callback);
                };
                
                $doCallback = function ($result) use ($code, $context, &$messages, &$time, &$timer) {
                    $endtime = $timer(true);
                    
                    $previous = \set_error_handler(array($this, 'errorCallback'));
                    $result = $this->invokeDump($result);
                    \set_error_handler($previous);
                    
                    $len = \mb_strlen($result);
                    $maxlen = 1850 - \mb_strlen($code);
                    
                    if($len > $maxlen) {
                        $result = \mb_substr($result, 0, $maxlen).\PHP_EOL.'...';
                    }
                    
                    $sizeformat = \count($this->timeformats) - 1;
                    $format = 0;
                    
                    $exectime = $endtime - $time;
                    while(\ceil($exectime) >= 1000.0 && $format < $sizeformat) {
                        $exectime /= 1000;
                        $format++;
                    }
                    $exectime = \ceil($exectime);
                    
                    $messages[] = $context->say($context->message->author.\CharlotteDunois\Yasmin\Models\Message::$replySeparator.'Executed callback after '.$exectime.$this->timeformats[$format].'.'.\PHP_EOL.\PHP_EOL.'```php'.\PHP_EOL.$result.\PHP_EOL.'```'.($len > $maxlen ? \PHP_EOL.'Original length: '.$len : ''));
                };
                
                $prev = \set_error_handler(array($this, 'errorCallback'));
                
                $endtime = null;
                $time = $timer();
                
                $evalcode = 'namespace CharlotteDunois\\Livia\\Commands\\EvalNamespace\\'.\preg_replace('/[^a-z]/i', '', \bin2hex(\random_bytes(10)).\sha1(\time())).';'.
                                \PHP_EOL.$code;
                
                $result = (function () use ($evalcode, $context, &$doCallback) {
                    $client = $this->client;
                    return eval($evalcode);
                })();
                
                if(!($result instanceof \React\Promise\PromiseInterface)) {
                    $endtime = $timer();
                    $result = \React\Promise\resolve($result);
                }
                
                return $result->then(function ($result) use ($code, $context, &$messages, &$prev, &$endtime, $time, &$timer) {
                    if($endtime === null) {
                        $endtime = $timer();
                    }
                    
                    $this->lastResult = $result;
                    $result = $this->invokeDump($result);
                    
                    \set_error_handler($prev);
                    
                    $len = \mb_strlen($result);
                    $maxlen = 1850 - \mb_strlen($code);
                    
                    if($len > $maxlen) {
                        $result = \mb_substr($result, 0, $maxlen).\PHP_EOL.'...';
                    }
                    
                    $sizeformat = \count($this->timeformats) - 1;
                    $format = 0;
                    
                    $exectime = $endtime - $time;
                    while(\ceil($exectime) >= 1000.0 && $format < $sizeformat) {
                        $exectime /= 1000;
                        $format++;
                    }
                    $exectime = \ceil($exectime);
                    
                    $messages[] = $context->say($context->message->author.\CharlotteDunois\Yasmin\Models\Message::$replySeparator.'Executed in '.$exectime.$this->timeformats[$format].'.'.\PHP_EOL.\PHP_EOL.'```php'.\PHP_EOL.$result.\PHP_EOL.'```'.($len > $maxlen ? \PHP_EOL.'Original length: '.$len : ''));
                    return $messages;
                })->done($resolve, $reject);
            }))->otherwise(function ($e) use ($code, $context, &$messages, &$prev) {
                \set_error_handler($prev);
                
                $e = (string) $e;
                $len = \mb_strlen($e);
                $maxlen = 1900 - \mb_strlen($code);
                
                if($len > $maxlen) {
                    $e = \mb_substr($e, 0, $maxlen).\PHP_EOL.'...';
                }
                
                $messages[] = $context->say($context->message->author.\PHP_EOL.'```php'.\PHP_EOL.$code.\PHP_EOL.'```'.\PHP_EOL.'Error: ```'.\PHP_EOL.$e.\PHP_EOL.'```');
                return $messages;
            });
        }
        
        /**
         * @param mixed  $result
         * @return string
         * @throws \RuntimeException
         */
        function invokeDump($result) {
            if($this->xdebug) {
                \ob_start('mb_output_handler');
                
                $old = \ini_get('xdebug.var_display_max_depth');
                \ini_set('xdebug.var_display_max_depth', 1);
                
                \xdebug_var_dump($result);
                \ini_set('xdebug.var_display_max_depth', $old);
                $result = @\ob_get_clean(); // Fixes a xdebug bug "Cannot modify header information"
                
                $result = \substr($result, (\strpos($result, "\n") + 1));
                $result = \preg_replace("/#(\d+) \((\d+)\) {\n\s*...\n\s*}/u", '#$1 ($2) { }', $result);
            } else {
                $data = $this->cloner->cloneVar($result);
                $result = $this->dumper->dump($data->withMaxDepth(1), true);
            }
            
            $email = $this->client->user->email;
            $tokenregex = \preg_quote($this->client->token, '/');
            $emailregex = (!empty($email) ? \preg_quote($email, '/') : null);
            
            $result = \preg_replace('/string\(\d+\) "'.$tokenregex.'"'.($emailregex !== null ? '|string\(\d+\) "'.$emailregex.'"' : '').'/iu', 'string(10) "[redacted]"', $result);
            $result = \preg_replace('/'.$tokenregex.($emailregex !== null ? '|'.$emailregex : '').'/iu', '[redacted]', $result);
            
            return $result;
        }
        
        /**
         * @return bool
         * @throws \ErrorException
         */
        function errorCallback($errno, $errstr, $errfile, $errline) {
            // Latter fixes a bug
            if(\error_reporting() === 0) {
                return true;
            }
            
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        }
        
        /**
         * @return mixed
         */
        function timer(?\CharlotteDunois\Livia\Utils\HRTimer &$hrtime, bool $callback = false) {
            if(!$hrtime) {
                $hrtime = new \CharlotteDunois\Livia\Utils\HRTimer();
                return ($hrtime->start() ?? 0);
            }
            
            if($callback) {
                return $hrtime->time();
            }
            
            return $hrtime->stop();
        }
    });
};
