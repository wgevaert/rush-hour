<?php

namespace RushHour\Test\Services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RushHour\Exception\SerializedException;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use RushHour\Services\CarPositionBoardSerializer;

class CarPositionBoardSerializerTest extends TestCase
{
    #[DataProvider('serializedBoardsDataProvider')]
    public function testUnserializeBoardAndSerializeBoard(string $boardString): void
    {
        $serializer = new CarPositionBoardSerializer();
        $board = $serializer->unserializeBoard($boardString);
        $string = $serializer->serializeBoard($board);
        $this->assertSame($boardString, $string);
    }

    public static function serializedBoardsDataProvider(): array {
        return [
          ['5,5$1,0;r1,1D2|b2,2R2'],
          ["6,6$0,2;a1,3D2|r0,2R2"],
        ];
    }
}
