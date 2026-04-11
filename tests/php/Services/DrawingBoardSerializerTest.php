<?php

namespace RushHour\Test\Services;

use PHPUnit\Framework\TestCase;
use RushHour\Models\Board;
use RushHour\Services\BoardDrawer;
use RushHour\Services\BoardDrawingParser;
use RushHour\Services\DrawingBoardSerializer;

class DrawingBoardSerializerTest extends TestCase
{
    private function getSerializer(): DrawingBoardSerializer {
        return new DrawingBoardSerializer( new BoardDrawer, new BoardDrawingParser );
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
