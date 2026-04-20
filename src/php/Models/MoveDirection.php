<?php

namespace RushHour\Models;

/**
 * Directions that a move can be made in
 */
enum MoveDirection
{
    case NORTH;
    case EAST;
    case SOUTH;
    case WEST;
}
