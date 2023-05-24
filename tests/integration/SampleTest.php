<?php

declare(strict_types=1);

namespace Wizhippo\Tests\Integration\Schedule;

/**
 * @group integration
 * @coversNothing
 */
final class SampleTest extends IbexaKernelTestCase
{
    public function testCompilesSuccessfully(): void
    {
        self::bootKernel();

        $this->expectNotToPerformAssertions();
    }
}
