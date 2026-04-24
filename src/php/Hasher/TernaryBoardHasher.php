<?php

namespace RushHour\Hasher;

use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;

/**
 * A boardHasher that gives each square one of possible values 0 (empty), 1 (occupied by right car), 2 (occupied by down car) and then converts to binary string.
 * Legal moves in a board can never move two of these hashes onto eachother.
 */
class TernaryBoardHasher implements BoardHasher
{
    public function hashBoard(Board $board): string {
        $boardMap = $this->emptyBoardMap($board);
        foreach ($board->getCars() as $car) {
            $boardMap = $this->addCar($car, $boardMap);
        }
        return $this->mapToString($boardMap);
    }

    private function emptyBoardMap(Board $board): array {
        $bottomRight = $board->getBottomRight();
        $singleRow = array_fill(0,$bottomRight->x,0);
        return array_fill(0,$bottomRight->y,$singleRow);
    }

    private function addCar(Car $car, $boardMap): array {
        foreach($car->getCoordinates() as $pos) {
            if ($car->direction === CarDirection::RIGHT) {
                $boardMap[$pos->y-1][$pos->x-1] = 1;
            } else {
                $boardMap[$pos->y-1][$pos->x-1] = 2;
            }
        }
        return $boardMap;
    }

    private function mapToString(array $boardMap) {
        $flattenedMap = array_merge(...$boardMap);
        $result = '';
        // Byte can hold a max value of 255 and 3^5 = 243 so we encode 5 numbers into a single byte
        for($i=0;$i<count($flattenedMap);$i+=5) {
            $currentByte = 0;
            $radix=1;
            for($j=0;$j<5;$j++){
                $currentByte += $radix * ($flattenedMap[$i+$j]??0);
                $radix*=3;
            }
            $result .= chr($currentByte);
        }
        return $result;
    }
}
