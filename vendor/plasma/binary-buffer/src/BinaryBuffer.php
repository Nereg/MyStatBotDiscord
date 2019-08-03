<?php
/**
 * Plasma Binary Buffer component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/binary-buffer/blob/master/LICENSE
*/

namespace Plasma;

/**
 * A binary buffer takes binary data and buffers it. Several methods are available to get specific data.
 */
class BinaryBuffer implements \ArrayAccess {
    /**
     * @var string
     */
    protected $buffer = '';
    
    /**
     * @var bool|null
     */
    protected static $gmp;
    
    /**
     * Constructor.
     * @param string  $buffer
     */
    function __construct(string $buffer = '') {
        if(static::$gmp === null) {
            static::$gmp = \extension_loaded('gmp');
        }
        
        $this->buffer = $buffer;
    }
    
    /**
     * Append data to the buffer.
     * @param string  $data
     * @return $this
     */
    function append(string $data): self {
        $this->buffer .= $data;
        return $this;
    }
    
    /**
     * Prepends data to the buffer.
     * @param string  $data
     * @return $this
     */
    function prepend(string $data): self {
        $this->buffer = $data.$this->buffer;
        return $this;
    }
    
    /**
     * Slice the buffer and only keep a subset.
     * @param int       $offset
     * @param int|null  $length
     * @return $this
     */
    function slice(int $offset, ?int $length = null): self {
        if($length === null) {
            $this->buffer = \substr($this->buffer, $offset);
        } else {
            $this->buffer = \substr($this->buffer, $offset, $length);
        }
        
        return $this;
    }
    
    /**
     * Get the buffer size/length.
     * @return int
     */
    function getSize(): int {
        return \strlen($this->buffer);
    }
    
    /**
     * Get the contents.
     * @return string
     */
    function getContents(): string {
        return $this->buffer;
    }
    
    /**
     * Clears the buffer.
     * @return $this
     */
    function clear(): self {
        $this->buffer = '';
        return $this;
    }
    
    /**
     * Parses a 1 byte / 8 bit integer (0 to 255).
     * @return int
     */
    function readInt1(): int {
        return \ord($this->read(1));
    }
    
    /**
     * Parses a 2 byte / 16 bit integer (0 to 64 K / 0xFFFF).
     * @return int
     */
    function readInt2(): int {
        return \unpack('v', $this->read(2))[1];
    }
    
    /**
     * Parses a 3 byte / 24 bit integer (0 to 16 M / 0xFFFFFF).
     * @return int
     */
    function readInt3(): int {
        return \unpack('V', $this->read(3)."\0")[1];
    }
    
    /**
     * Parses a 4 byte / 32 bit integer (0 to 4 G / 0xFFFFFFFF).
     * @return int
     */
    function readInt4(): int {
        return \unpack('V', $this->read(4))[1];
    }
    
    /**
     * Parses a 8 byte / 64 bit integer (0 to 2^64-1).
     * @return int|string
     * @throws \RuntimeException
     */
    function readInt8() {
        $strInt = $this->read(8);
        
        if(\PHP_INT_SIZE > 4) {
            return \unpack('P', $strInt)[1];
        }
        
        if(static::$gmp) {
            $result = \gmp_import($strInt, 1, (\GMP_LSW_FIRST | \GMP_LITTLE_ENDIAN));
            if($result === false) {
                throw new \RuntimeException('Unable to convert input into an integer');
            }
            
            if(\gmp_cmp($result, '9223372036854775808') !== -1) {
                $result = \gmp_sub($result, '18446744073709551616'); // $result -= (1 << 64)
            }
            
            $result = \gmp_strval($result);
        } else {
            $result = \bcadd('0', \unpack('n', \substr($strInt, 0, 2))[1]);
            $result = \bcmul($result, '65536');
            $result = \bcadd($result, \unpack('n', \substr($strInt, 2, 2))[1]);
            $result = \bcmul($result, '65536');
            $result = \bcadd($result, \unpack('n', \substr($strInt, 4, 2))[1]);
            $result = \bcmul($result, '65536');
            $result = \bcadd($result, \unpack('n', \substr($strInt, 6, 2))[1]);
            
            // 9223372036854775808 is equal to (1 << 63)
            if(\bccomp($result, '9223372036854775808') !== -1) {
                $result = \bcsub($result, '18446744073709551616'); // $result -= (1 << 64)
            }
        }
        
        return $result;
    }
    
    /**
     * Reads a single precision float.
     * @return float
     */
    function readFloat(): float {
        return \unpack('g', $this->read(4))[1];
    }
    
    /**
     * Reads a double precision float.
     * @return float
     */
    function readDouble(): float {
        return \unpack('e', $this->read(8))[1];
    }
    
    /**
     * Parses length-encoded binary integer from the MySQL protocol.
     * Returns the decoded integer 0 to 2^64 or `null` for special null int.
     * @return int|null
     */
    function readIntLength(): ?int {
        $f = $this->readInt1();
        if($f <= 250) {
            return $f;
        }
        
        if($f === 251) {
            return null;
        }
        
        if($f === 252) {
            return $this->readInt2();
        }
        
        if($f === 253) {
            return $this->readInt3();
        }
        
        return $this->readInt8();
    }
    
    /**
     * Parses a length-encoded binary string from the MySQL protocol. If length is null, `null` will be returned.
     * @param int|null  $length
     * @return string|null
     */
    function readStringLength(?int $length = null): ?string {
        $length = ($length !== null ? $length : $this->readIntLength());
        if($length === null) {
            return null;
        }
        
        return $this->read($length);
    }
    
    /**
     * Reads NULL-terminated C string.
     * @return string
     * @throws \InvalidArgumentException
     */
    function readStringNull(): string {
        $pos = \strpos($this->buffer, "\0");
        if($pos === false) {
            throw new \InvalidArgumentException('Missing NULL character');
        }
        
        $str = $this->read($pos);
        $this->read(1); // discard NULL byte
        
        return $str;
    }
    
    /**
     * Writes a 1 byte / 8 bit integer (0 to 255).
     * @param int  $int
     * @return string
     */
    static function writeInt1(int $int): string {
        return \chr($int);
    }
    
    /**
     * Writes a 2 bytes / 16 bit integer (0 to 64 K / 0xFFFF).
     * @param int  $int
     * @return string
     */
    static function writeInt2(int $int): string {
        return \pack('v', $int);
    }
    
    /**
     * Writes a 3 byte / 24 bit integer (0 to 16 M / 0xFFFFFF).
     * @param int  $int
     * @return string
     */
    static function writeInt3(int $int): string {
        return \substr(\pack('V', $int), 0, 3);
    }
    
    /**
     * Writes a 4 byte / 32 bit integer (0 to 4 G / 0xFFFFFFFF).
     * @param int  $int
     * @return string
     */
    static function writeInt4(int $int): string {
        return \pack('V', $int);
    }
    
    /**
     * Writes a 8 byte / 64 bit integer (0 to 2^64-1).
     * @param string|int  $int
     * @return string
     */
    static function writeInt8($int): string {
        if(\PHP_INT_SIZE > 4) {
            return \pack('P', ((int) $int));
        }
        
        if(static::$gmp) {
            $int = \gmp_init($int);
            
            if(\gmp_cmp($int, '0') === -1) {
                // 18446744073709551616 is equal to (1 << 64)
                $int = \gmp_add($int, '18446744073709551616');
            }
            
            return \gmp_export($int, 1, (\GMP_LSW_FIRST | \GMP_LITTLE_ENDIAN));
        }
        
        if(\bccomp($int, '0') === -1) {
            // 18446744073709551616 is equal to (1 << 64)
            $int = \bcadd($int, '18446744073709551616');
        }
        
        return \pack('v', \bcmod(\bcdiv($int, '281474976710656'), '65536')).
            \pack('v', \bcmod(\bcdiv($int, '4294967296'), '65536')).
            \pack('v', \bcdiv($int, '65536'), '65536').
            \pack('v', \bcmod($int, '65536'));
    }
    
    /**
     * Writes a single precision float.
     * @param float  $float
     * @return string
     */
    static function writeFloat(float $float): string {
        return \pack('g', $float);
    }
    
    /**
     * Writes a double precision float.
     * @param float  $float
     * @return string
     */
    static function writeDouble(float $float): string {
        return \pack('e', $float);
    }
    
    /**
     * Builds length-encoded binary string from the MySQL protocol.
     * @param string|null  $str
     * @return string
     */
    static function writeStringLength(?string $str): string {
        if($str === null) {
            // \xFB (251)
            return "\xFB";
        }
        
        $length = \strlen($str);
        if($length <= 250) {
            return static::writeInt1($length).$str;
        }
        
        if($length <= 0xFFFF) { // max 2^16: \xFC (252)
            return "\xFC".static::writeInt2($length).$str;
        }
        
        if($length <= 0xFFFFFF) { // max 2^24: \xFD (253)
            return "\xFD".static::writeInt3($length).$str;
        }
        
        return "\xFE".static::writeInt8($length).$str; // max 2^64: \xFE (254)
    }
    
    /**
     * Reads a specified length from the buffer (and discards the read part from the buffer).
     * @param int  $length
     * @return string
     * @throws \OverflowException
     */
    function read(int $length): string {
        $size = $this->getSize();
        if($size < $length) {
            throw new \OverflowException('Trying to read behind buffer, requested '.$length.' bytes, only got '.$size.' bytes');
        }
        
        $str = \substr($this->buffer, 0, $length);
        $this->buffer = \substr($this->buffer, $length);
        
        return $str;
    }
    
    /**
     * @param int  $offset
     * @return bool
     * @internal
     */
    function offsetExists($offset) {
        return isset($this->buffer[$offset]);
    }
    
    /**
     * @param int  $offset
     * @return string
     * @internal
     */
    function offsetGet($offset) {
        return $this->buffer[$offset];
    }
    
    /**
     * @param int     $offset
     * @param string  $value
     * @return void
     * @throws \InvalidArgumentException
     * @internal
     */
    function offsetSet($offset, $value) {
        if(!\is_string($value)) {
            throw new \InvalidArgumentException('Illegal value of type '.\gettype($value));
        }
        
        $this->buffer[$offset] = $value;
    }
    
    /**
     * @param int  $offset
     * @return void
     * @throws \BadMethodCallException
     * @internal
     */
    function offsetUnset($offset) {
        throw new \BadMethodCallException('String offsets can not be unset');
    }
}
