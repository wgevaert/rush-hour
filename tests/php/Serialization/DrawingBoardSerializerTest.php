<?php

namespace RushHour\Test\Serialization;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\BoardDrawingParser;
use RushHour\Serialization\DrawingBoardSerializer;

class DrawingBoardSerializerTest extends TestCase
{
    private function getSerializer(): DrawingBoardSerializer
    {
        return new DrawingBoardSerializer(new BoardDrawer(), new BoardDrawingParser());
    }

    public function testReadAndDraw(): void
    {
        $serializer = $this->getSerializer();
        $boardDrawing =
            "@@@@@@@@\n" .
            "@kkk.e.@\n" .
            "@..j.e.@\n" .
            "@rrj.ea.\n" .
            "@ihhcca@\n" .
            "@iggd.b@\n" .
            "@fffd.b@\n" .
            "@@@@@@@@";
        $board = $serializer->unserializeBoard($boardDrawing);
        $drawing = $serializer->serializeBoard($board);
        $this->assertSame(
            $boardDrawing,
            $drawing
        );
    }
}
