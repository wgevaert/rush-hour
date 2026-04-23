<?php

namespace RushHour\Config;

use RuntimeException;

interface Config
{
    public function get(string $key): mixed;
}
