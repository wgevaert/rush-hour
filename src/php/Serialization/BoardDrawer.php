<?php

namespace RushHour\Serialization;

use LengthException;
use LogicException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use RushHour\Exception\SerializedException;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;

/**
 * Can make a drawing from a board.
 */
class BoardDrawer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const BORDER = '@';
    public const EMPTY = '.';

    private ?string $carChar = null;

    public function draw(Board $board): string
    {
        $emptyBoard = $this->emptyBoardLines($board);
        $boardArray = $this->addCars($emptyBoard, $board);

        return implode("\n", $boardArray);
    }

    public function setCarChar(?string $char = null)
    {
        if ($char !== null && strlen($char) !== 1) {
            throw new LengthException('A char is a string of length 1');
        }
        $this->carChar = $char;
    }

    /**
     * Makes an outline of a board
     * Example:
     * [
     *   '@@@@@',
     *   '@...@',
     *   '@...@',
     *   '@...@',
     *   '@@@@@',
     * ];
     * @param Board $board The board that knows the dimensions
     * @return list<string> A drawing of the board with only outlines
     */
    private function emptyBoardLines(Board $board): array
    {
        $boardDimensions = $board->getBottomRight();
        $borderLine = str_repeat(self::BORDER, $boardDimensions->x + 2);
        $insideLine = self::BORDER . str_repeat(self::EMPTY, $boardDimensions->x) . self::BORDER;

        $result = array_fill(1, $boardDimensions->y, $insideLine);
        array_unshift($result, $borderLine);
        $result [] = $borderLine;

        $result = $this->addExit(array_values($result), $board);
        return $result;
    }

    /**
     * Adds the exit from $board to the drawing in $result
     *
     * @param list<string> $result Drawing of the board, without exit yet
     * @param Board $board The board that knows where the exit is
     * @return list<string> Drawing with exit
     */
    private function addExit(array $result, Board $board): array
    {
        return $this->setSafe($result, $board->getExit(), self::EMPTY);
    }

    /**
     * Adds the cars from $board to the drawing in $result
     *
     * @param list<string> $result Drawing of the board, without cars yet
     * @param Board $board The board that knows where the cars are
     * @return list<string> Drawing with exit
     */
    private function addCars(array $result, Board $board): array
    {
        foreach ($board->getCars() as $name => $car) {
            $nameChar = $this->makeSingleChar($name);
            foreach ($car->getCoordinates() as $pos) {
                $result = $this->setSafe($result, $pos, $nameChar);
            }
        }
        return $result;
    }

    /**
     * Sets char at $pos to $value
     *
     * @param list<string> $result Initial drawing of the board
     * @param Coordinate $pos
     * @param string $value
     * @return list<string> Drawing with char at $pos set to $value
     */
    private function setSafe(array $result, Coordinate $pos, string $value)
    {
        if (!isset($result[$pos->y]) || strlen($result[$pos->y]) < $pos->x) {
            throw new LogicException("Cannot place outside of drawn board");
        }
        if (strlen($value) !== 1) {
            throw new LogicException("Can only place single char at single position");
        }
        $result[ $pos->y ][ $pos->x ] = $value[0];

        /** @var list<string> $result */
        return $result;
    }

    private function makeSingleChar(string $name): string
    {
        if ($this->carChar !== null) {
            return $this->carChar;
        }
        return $name[0];
    }
}
