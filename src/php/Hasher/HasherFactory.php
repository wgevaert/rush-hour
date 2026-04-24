<?php

namespace RushHour\Hasher;

use RushHour\Models\Board;

class HasherFactory {
    public const HASHERS = [
        'position' => CarPositionBoardHasher::class,
        'ternary' => TernaryBoardHasher::class,
    ];

    static function getHasher( string $hasherId ): BoardHasher {
        $hasherClass = self::HASHERS[$hasherId] ?? false;
        if ( $hasherClass === false ) {
            throw new UserErrorException("Unknown hasher");
        }

        return new $hasherClass;
    }

    static function getBestHasher( Board $board ): BoardHasher {
        $minStrlen = PHP_INT_MAX;
        $bestHasher = null;
        foreach (array_keys(self::HASHERS) as $hasherId) {
            $hasher = self::getHasher($hasherId);
            $hashLength = strlen($hasher->hashBoard($board));
            if ( $hashLength < $minStrlen) {
                $bestHasher = $hasher;
                $minStrlen = $hashLength;
            }
        }
        return $bestHasher;
    }
}
