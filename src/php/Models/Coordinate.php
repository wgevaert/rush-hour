<?php

namespace RushHour\Models;

/**
 * A 2-dimensional Cartesian coordinate
 */
class Coordinate
{
    public function __construct(
        public int $x,
        public int $y
    ) {
    }
}
