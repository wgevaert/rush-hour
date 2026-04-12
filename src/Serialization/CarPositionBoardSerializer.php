<?php

namespace RushHour\Serialization;

use RushHour\Exception\SerializedException;
use RushHour\Models\Board;
use RushHour\Models\Car;
use RushHour\Models\CarDirection;
use RushHour\Models\Coordinate;
use LogicException;

/**
 * A serializer that gives a string consisting of board size, board exit location, and cars with name.
 */
class CarPositionBoardSerializer implements BoardSerializer
{
    public const SEPARATOR_SIZE = '$';
    public const SEPARATOR_EXIT = ';';
    public const SEPARATOR_COORDINATE = ',';
    public const SEPARATOR_CARS = '|';
    public const RIGHT = 'R';
    public const DOWN = 'D';

    public function serializeBoard(Board $board): string
    {
        return $this->serializeSize($board) . self::SEPARATOR_SIZE
            . $this->serializeExit($board) . self::SEPARATOR_EXIT
            . $this->serializeCars($board);
    }

    public function unserializeBoard(string $serializedBoard): Board
    {
        [ $size, $exit, $cars ] = $this->splitSerialized($serializedBoard);
        $board = $this->boardFromSerializedSize($size);
        $board->setExit($this->parseCoordinate($exit));
        foreach ($this->parseCars($cars) as $carName => $car) {
            $board->addCar($car, $carName);
        }

        return $board;
    }

    private function serializeCars(Board $board): string
    {
        $serializedCars = [];
        foreach ($board->getCars() as $carName => $car) {
            $serializedCars [] = $carName . $this->serializeCar($car);
        }
        return implode(self::SEPARATOR_CARS, $serializedCars);
    }

    private function serializeExit(Board $board): string
    {
        return $this->serializeCoordinate($board->getExit());
    }

    private function serializeSize(Board $board): string
    {
        return $this->serializeCoordinate($board->getBottomRight());
    }

    private function serializeCar(Car $car): string
    {
        $direction = match ($car->direction) {
            CarDirection::RIGHT => self::RIGHT,
            CarDirection::DOWN => self::DOWN
        };
        return $this->serializeCoordinate($car->position) . $direction . $car->length;
    }

    private function serializeCoordinate(Coordinate $coordinate): string
    {
        return $coordinate->x . self::SEPARATOR_COORDINATE . $coordinate->y;
    }

    /**
     * @param string $serializedBoard
     * @return list{string, string, string}
     */
    private function splitSerialized(string $serializedBoard): array
    {
        [ $size, $serializedWithoutSize ] = $this->splitStringOnSeparator($serializedBoard, self::SEPARATOR_SIZE);
        [ $exit, $cars ] = $this->splitStringOnSeparator($serializedWithoutSize, self::SEPARATOR_EXIT);
        return [ $size, $exit, $cars ];
    }

    private function boardFromSerializedSize(string $size): Board
    {
        $bottomRight = $this->parseCoordinate($size);
        return new Board($bottomRight->x, $bottomRight->y);
    }

    /**
     * @param string $cars Serialized cars
     * @return iterable<string, Car> Name of car and Car
     */
    private function parseCars(string $cars): iterable
    {
        if (empty($cars)) {
            return [];
        }
        $carsArray = explode(self::SEPARATOR_CARS, $cars);
        foreach ($carsArray as $namedCar) {
            [ $carName, $car ] = $this->splitCarName($namedCar);
            $parsedCar = $this->parseCar($car);
            yield $carName => $parsedCar;
        }
    }

    private function parseCar(string $car): Car
    {
        [ $positionString, $directionString, $lengthString ] = $this->splitCar($car);
        $position = $this->parseCoordinate($positionString);
        $direction = match ($directionString) {
            self::RIGHT => CarDirection::RIGHT,
            self::DOWN => CarDirection::DOWN,
            default => throw new LogicException(
                "Splitting of car went wrong, expected direction but received " . json_encode($directionString)
            ),
        };
        $length = $this->parseInt($lengthString);
        return new Car($position, $direction, $length);
    }

    /**
     * @param string $car A single serialized car
     * @return list{string, string, string} A car split into its constituent components
     */
    private function splitCar(string $car): array
    {
        $positionLength = strcspn($car, self::RIGHT . self::DOWN);
        if ($positionLength >= strlen($car) - 1) {
            throw new SerializedException('Serialized car should contain ' . self::RIGHT . ' or ' . self::DOWN);
        }
        $positionString = substr($car, 0, $positionLength);
        $directionString = substr($car, $positionLength, 1);
        $lengthString = substr($car, $positionLength + 1);

        return [ $positionString, $directionString, $lengthString ];
    }

    /**
     * @param string $namedCar Name of car and car
     * @return list{string, string} The car name and the car
     */
    private function splitCarName(string $namedCar): array
    {
        $nameLength = strcspn($namedCar, '0123456789');
        if ($nameLength >= strlen($namedCar)) {
            throw new SerializedException('Expected positive digit in serialized car');
        }
        $name = substr($namedCar, 0, $nameLength);
        $car = substr($namedCar, $nameLength);

        return [ $name, $car ];
    }

    private function parseCoordinate(string $coordinate): Coordinate
    {
        [ $xString, $yString ] = $this->splitStringOnSeparator($coordinate, self::SEPARATOR_COORDINATE);
        $x = $this->parseInt($xString);
        $y = $this->parseInt($yString);

        return new Coordinate($x, $y);
    }

    /**
     * @param string $string The string to split
     * @param non-empty-string $separator The separator to split string on
     *
     * @throws SerializedException If $separator is not found
     * @return list{string, string} A string split on the first occurrence of $separator
     */
    private function splitStringOnSeparator(string $string, string $separator): array
    {
        $parts = explode($separator, $string, 2);
        if (count($parts) < 2) {
            throw new SerializedException("Expected $separator in serialized string");
        }

        return $parts;
    }

    private function parseInt(string $intString): int
    {
        $int = filter_var($intString, FILTER_VALIDATE_INT);
        if ($int === false) {
            throw new SerializedException('Could not parse as int');
        }
        return $int;
    }
}
