<?php

namespace Sugarcrm\Tidbit\Core;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Class Random
 *
 * Single source of randomness for data generation.
 *
 * When a seed is set (--base_time), a Mt19937 engine is used, so the same
 * base time regenerates the same data. Without a seed the default Randomizer
 * engine is used, which draws from the same CSPRNG as random_int().
 *
 * @package Sugarcrm\Tidbit\Core
 */
class Random
{
    protected static ?Randomizer $randomizer = null;

    /**
     * Make all further draws reproducible for the given seed
     */
    public static function seed(int $seed): void
    {
        static::$randomizer = new Randomizer(new Mt19937($seed));
    }

    /**
     * Drop the seed, so further draws are taken from the CSPRNG again
     */
    public static function reset(): void
    {
        static::$randomizer = null;
    }

    /**
     * Randomizer in use, seeded or not
     */
    public static function getRandomizer(): Randomizer
    {
        if (static::$randomizer === null) {
            static::$randomizer = new Randomizer();
        }

        return static::$randomizer;
    }

    /**
     * Random integer between $min and $max, both included
     */
    public static function getInt(int $min, int $max): int
    {
        return static::getRandomizer()->getInt($min, $max);
    }

    /**
     * Shuffled copy of $array, with keys re-indexed as shuffle() does
     */
    public static function shuffleArray(array $array): array
    {
        return static::getRandomizer()->shuffleArray($array);
    }
}
