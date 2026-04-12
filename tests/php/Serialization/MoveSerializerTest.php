<?php

namespace RushHour\Test\Serialization;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Move;
use RushHour\Models\MoveDirection;
use RushHour\Serialization\MoveSerializer;

class MoveSerializerTest extends TestCase
{
    public function testUnserializeAndSerializeMove(): void
    {
        $moveString = 'r2S';
        $serializer = new MoveSerializer();
        $move = $serializer->unserializeMove($moveString);
        $string = $serializer->serializeMove($move);

        $this->assertSame($moveString, $string);
    }
}
