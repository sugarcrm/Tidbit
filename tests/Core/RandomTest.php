<?php

namespace Sugarcrm\Tidbit\Tests\Core;

use Random\Engine\Mt19937;
use Random\Engine\Secure;
use Sugarcrm\Tidbit\Core\Random;
use Sugarcrm\Tidbit\Tests\TidbitTestCase;

/**
 * Class RandomTest
 * @package Sugarcrm\Tidbit\Tests\Core
 * @coversDefaultClass Sugarcrm\Tidbit\Core\Random
 */
class RandomTest extends TidbitTestCase
{
    protected function tearDown(): void
    {
        Random::reset();
        parent::tearDown();
    }

    /**
     * Without a seed we should keep drawing from the CSPRNG, as random_int() does
     *
     * @covers ::getRandomizer
     * @covers ::reset
     */
    public function testUnseededRandomizerUsesSecureEngine()
    {
        $this->assertInstanceOf(Secure::class, Random::getRandomizer()->engine);

        Random::seed(1234);
        $this->assertInstanceOf(Mt19937::class, Random::getRandomizer()->engine);

        Random::reset();
        $this->assertInstanceOf(Secure::class, Random::getRandomizer()->engine);
    }

    /**
     * @covers ::getInt
     */
    public function testGetIntStaysWithinBounds()
    {
        for ($i = 0; $i < 100; $i++) {
            $value = Random::getInt(5, 10);
            $this->assertTrue($value >= 5 && $value <= 10);
        }

        $this->assertEquals(7, Random::getInt(7, 7));
    }

    /**
     * Same seed, same numbers -- this is what --base_time promises
     *
     * @covers ::seed
     * @covers ::getInt
     */
    public function testSeedMakesIntegersReproducible()
    {
        Random::seed(1451606400);
        $first = [];
        for ($i = 0; $i < 20; $i++) {
            $first[] = Random::getInt(0, 1000000);
        }

        Random::seed(1451606400);
        $second = [];
        for ($i = 0; $i < 20; $i++) {
            $second[] = Random::getInt(0, 1000000);
        }

        $this->assertSame($first, $second);
    }

    /**
     * @covers ::seed
     * @covers ::getInt
     */
    public function testDifferentSeedsGiveDifferentIntegers()
    {
        Random::seed(1451606400);
        $first = [];
        for ($i = 0; $i < 20; $i++) {
            $first[] = Random::getInt(0, 1000000);
        }

        Random::seed(1451606401);
        $second = [];
        for ($i = 0; $i < 20; $i++) {
            $second[] = Random::getInt(0, 1000000);
        }

        $this->assertNotSame($first, $second);
    }

    /**
     * @covers ::shuffleArray
     */
    public function testShuffleArrayIsReproducibleUnderSeedAndKeepsAllValues()
    {
        $source = range(1, 20);

        Random::seed(1451606400);
        $first = Random::shuffleArray($source);

        Random::seed(1451606400);
        $second = Random::shuffleArray($source);

        $this->assertSame($first, $second);
        $this->assertSame(array_keys($source), array_keys($first));

        $sorted = $first;
        sort($sorted);
        $this->assertSame($source, $sorted);
    }
}
