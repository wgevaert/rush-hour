<?php

namespace RushHour\Exception;

use UnexpectedValueException;

/**
 * An error that means the user did something wrong, i.e. they provided unexpected values.
 * Should contain a message safe for showing to the user.
 * If code is between 400 and 499, it will be used as the HTTP response code.
 */
class UserErrorException extends UnexpectedValueException
{
}
