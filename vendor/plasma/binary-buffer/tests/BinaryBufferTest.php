<?php
/**
 * Plasma Binary Buffer component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/binary-buffer/blob/master/LICENSE
*/

namespace Plasma\BinaryBuffer\Tests;

class BinaryBufferTest extends \PHPUnit\Framework\TestCase {
    function testCreateFromConstructor() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame('hello', $buffer->getContents());
    }
    
    function testCreateFromConstructorEmpty() {
        $buffer = new \Plasma\BinaryBuffer();
        $this->assertSame('', $buffer->getContents());
    }
    
    function testAppend() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame($buffer, $buffer->append('world'));
        $this->assertSame('helloworld', $buffer->getContents());
    }
    
    function testPrepend() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame($buffer, $buffer->prepend('world'));
        $this->assertSame('worldhello', $buffer->getContents());
    }
    
    function testSlice() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame($buffer, $buffer->slice(2, 2));
        $this->assertSame('ll', $buffer->getContents());
    }
    
    function testSliceNull() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame($buffer, $buffer->slice(2));
        $this->assertSame('llo', $buffer->getContents());
    }
    
    function testGetSize() {
        $buffer = new \Plasma\BinaryBuffer();
        $this->assertSame(0, $buffer->getSize());
        
        $buffer2 = new \Plasma\BinaryBuffer('hello');
        $this->assertSame(5, $buffer2->getSize());
    }
    
    function testClear() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame($buffer, $buffer->clear());
        $this->assertSame('', $buffer->getContents());
    }
    
    function testRead() {
        $buffer = new \Plasma\BinaryBuffer(\chr(50));
        $this->assertSame(\chr(50), $buffer->read(1));
    }
    
    function testReadBehind() {
        $buffer = new \Plasma\BinaryBuffer(\chr(50));
        
        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('Trying to read behind buffer, requested 2 bytes, only got 1 bytes');
        
        $buffer->read(2);
    }
    
    function testReadInt1() {
        $buffer = new \Plasma\BinaryBuffer(\chr(50));
        $this->assertSame(50, $buffer->readInt1());
    }
    
    function testReadInt2() {
        $buffer = new \Plasma\BinaryBuffer(\pack('v', 16211));
        $this->assertSame(16211, $buffer->readInt2());
    }
    
    function testReadInt3() {
        $buffer = new \Plasma\BinaryBuffer(\substr(\pack('V', 18520), 0, 3));
        $this->assertSame(18520, $buffer->readInt3());
    }
    
    function testReadInt4() {
        $buffer = new \Plasma\BinaryBuffer(\pack('V', 648023));
        $this->assertSame(648023, $buffer->readInt4());
    }
    
    function testReadInt8() {
        $buffer = new \Plasma\BinaryBuffer(\pack('P', 853493435));
        $this->assertSame(853493435, $buffer->readInt8());
    }
    
    function testReadFloat() {
        $buffer = new \Plasma\BinaryBuffer(\pack('g', 55.2));
        $this->assertEquals(55.2, $buffer->readFloat(), '', 0.001);
    }
    
    function testReadDouble() {
        $buffer = new \Plasma\BinaryBuffer(\pack('e', 4.9));
        $this->assertEquals(4.9, $buffer->readDouble(), '', 0.001);
    }
    
    function testReadIntLength() {
        $buffer = new \Plasma\BinaryBuffer(\chr(50));
        $this->assertSame(50, $buffer->readIntLength());
    }
    
    function testReadIntLength251Null() {
        $buffer = new \Plasma\BinaryBuffer(\chr(251));
        $this->assertNull($buffer->readIntLength());
    }
    
    function testReadIntLength2Byte() {
        $buffer = new \Plasma\BinaryBuffer(\chr(252).\pack('v', 12590));
        $this->assertSame(12590, $buffer->readIntLength());
    }
    
    function testReadIntLength3Byte() {
        $buffer = new \Plasma\BinaryBuffer(\chr(253).\substr(\pack('V', 325209), 0, 3));
        $this->assertSame(325209, $buffer->readIntLength());
    }
    
    function testReadIntLength8Byte() {
        $buffer = new \Plasma\BinaryBuffer(\chr(254).\pack('P', 598030945));
        $this->assertSame(598030945, $buffer->readIntLength());
    }
    
    function testReadIntLength8Byte2() {
        $buffer = new \Plasma\BinaryBuffer(\chr(255).\pack('P', 598030945));
        $this->assertSame(598030945, $buffer->readIntLength());
    }
    
    function testReadStringLength() {
        $buffer = new \Plasma\BinaryBuffer(\chr(254).\pack('P', 5).'hello');
        $this->assertSame('hello', $buffer->readStringLength());
    }
    
    function testReadStringLengthNull() {
        $buffer = new \Plasma\BinaryBuffer(\chr(251));
        $this->assertNull($buffer->readStringLength());
    }
    
    function testReadStringNull() {
        $buffer = new \Plasma\BinaryBuffer("hello\0");
        $this->assertSame('hello', $buffer->readStringNull());
    }
    
    function testReadStringNullMissingNull() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        
        $this->expectException(\InvalidArgumentException::class);
        $buffer->readStringNull();
    }
    
    function testWriteInt1() {
        $this->assertSame(\chr(50), \Plasma\BinaryBuffer::writeInt1(50));
    }

    function testWriteInt2() {
        $this->assertSame(\pack('v', 16211), \Plasma\BinaryBuffer::writeInt2(16211));
    }

    function testWriteInt3() {
        $this->assertSame(\substr(\pack('V', 18520), 0, 3), \Plasma\BinaryBuffer::writeInt3(18520));
    }

    function testWriteInt4() {
        $this->assertSame(\pack('V', 648023), \Plasma\BinaryBuffer::writeInt4(648023));
    }

    function testWriteInt8() {
        $this->assertSame(\pack('P', 853493435), \Plasma\BinaryBuffer::writeInt8(853493435));
    }

    function testWriteFloat() {
        $this->assertSame(\pack('g', 55.2), \Plasma\BinaryBuffer::writeFloat(55.2));
    }

    function testWriteDouble() {
        $this->assertSame(\pack('e', 4.9), \Plasma\BinaryBuffer::writeDouble(4.9));
    }
    
    function testWriteStringLength() {
        $this->assertSame(\chr(5).'hello', \Plasma\BinaryBuffer::writeStringLength('hello'));
    }
    
    function testWriteStringLength2() {
        $this->assertSame(\chr(252).\pack('v', 1250).\str_repeat('hello', 250), \Plasma\BinaryBuffer::writeStringLength(\str_repeat('hello', 250)));
    }
    
    function testWriteStringLength3() {
        $this->assertSame(\chr(253).\substr(\pack('V', 125000), 0, 3).\str_repeat('hello', 25000), \Plasma\BinaryBuffer::writeStringLength(\str_repeat('hello', 25000)));
    }
    
    function testWriteStringLength8() {
        $this->assertSame(\chr(254).\pack('P', 16777216).\str_repeat('a', 16777216), \Plasma\BinaryBuffer::writeStringLength(\str_repeat('a', 16777216)));
    }
    
    function testWriteStringLengthNull() {
        $this->assertSame(\chr(251), \Plasma\BinaryBuffer::writeStringLength(null));
    }
    
    function testArrayAccessExists() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertTrue(isset($buffer[2]));
        $this->assertFalse(isset($buffer[6]));
    }
    
    function testArrayAccessGet() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $this->assertSame('e', $buffer[1]);
    }
    
    function testArrayAccessSet() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        $buffer[1] = 'f';
        $this->assertSame('hfllo', $buffer->getContents());
    }
    
    function testArrayAccessSetNoString() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        
        $this->expectException(\InvalidArgumentException::class);
        $buffer[1] = null;
    }
    
    function testArrayAccessUnset() {
        $buffer = new \Plasma\BinaryBuffer('hello');
        
        $this->expectException(\BadFunctionCallException::class);
        unset($buffer[1]);
    }
}
