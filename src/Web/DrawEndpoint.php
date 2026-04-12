<?php

namespace RushHour\Web;

use UnexpectedValueException;
use Psr\Log\LoggerAwareTrait;
use RushHour\Models\Board;
use RushHour\Serialization\BoardDrawer;
use RushHour\Serialization\BoardSerializer;
use RushHour\Serialization\CarPositionBoardSerializer;

class DrawEndpoint extends BoardEndpoint
{
    public function execute(): array
    {
        return $this->drawBoard();
    }

    /**
     * Gives an array of lines of the drawn board
     *
     * Example:
     * [
     *   '@@@@@',
     *   '..rr@',
     *   '@bb.@',
     *   '@@@@@'
     * ]
     * @return array<string> An array with the lines of the board
     */
    private function drawBoard(): array
    {
        $drawer = new BoardDrawer();
        if ($this->logger !== null) {
            $drawer->setLogger($this->logger);
        }
        $boardString = $drawer->draw($this->board);
        return explode("\n", trim($boardString));
    }
}
