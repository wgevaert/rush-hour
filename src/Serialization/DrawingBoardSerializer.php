<?php

namespace RushHour\Services;

use RushHour\Models\Board;

class DrawingBoardSerializer implements BoardSerializer {
    public function __construct( private BoardDrawer $drawer, private BoardDrawingParser $parser )
    {
    }

    public function serializeBoard( Board $board ): string {
        return $this->drawer->draw( $board );
    }

    public function unserializeBoard( string $drawing ): Board {
        return $this->parser->boardFromDrawing( $drawing );
    }
}
