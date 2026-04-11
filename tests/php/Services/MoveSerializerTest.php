<?php

namespace RushHour\Test\Services;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Move;
use RushHour\Models\MoveDirection;
use RushHour\Services\MoveSerializer;

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
