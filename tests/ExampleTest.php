<?php

declare(strict_types=1);

namespace PoorPlebs\PackageTemplate\Tests;

use PHPUnit\Framework\TestCase;
use PoorPlebs\PackageTemplate\ExampleFile;

/**
 * @coversDefaultClass \PoorPlebs\PackageTemplate\ExampleFile
 */
class ExampleTest extends TestCase
{
    /**
     * @test
     * @covers \PoorPlebs\PackageTemplate\ExampleFile
     */
    public function it_tests_something(): void
    {
        $x = new ExampleFile();

        $this->assertSame(ExampleFile::class, get_class($x));
    }
}
