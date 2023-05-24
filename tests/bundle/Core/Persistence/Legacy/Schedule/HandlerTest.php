<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Tests\Core\Persistence\Legacy\Schedule;

use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway;
use Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Handler;
use Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Mapper;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Handler as SPIHandler;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Schedule as SPISchedule;

final class HandlerTest extends TestCase
{
    /**
     * Mocked schedule gateway instance.
     *
     * @var \Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway&\PHPUnit\Framework\MockObject\MockObject
     */
    private MockObject $gateway;

    /**
     * Mocked schedule mapper instance.
     *
     * @var \Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Mapper&\PHPUnit\Framework\MockObject\MockObject
     */
    private MockObject $mapper;

    private SPIHandler $scheduleHandler;

    protected function setUp(): void
    {
        $this->scheduleHandler = $this->getScheduleHandler();
    }

    /**
     * @covers \Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Handler::__construct
     * @covers \Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Handler::load
     */
    public function testLoad(): void
    {
        $this->gateway
            ->expects(self::once())
            ->method('getScheduleData')
            ->with(42)
            ->willReturn(
                [
                    [
                        'id' => 42,
                    ],
                ]
            )
        ;

        $schedule = new SPISchedule(['id' => 42]);

        $this->mapper
            ->expects(self::once())
            ->method('createScheduleFromRow')
            ->with([['id' => 42]])
            ->willReturn($schedule)
        ;

        self::assertSame($schedule, $this->scheduleHandler->load(42));
    }

    private function getScheduleHandler(): SPIHandler
    {
        $this->gateway = $this->createMock(Gateway::class);

        $this->mapper = $this->getMockBuilder(Mapper::class)
            ->setConstructorArgs(
                [
                ]
            )->getMock()
        ;

        return new Handler($this->gateway, $this->mapper);
    }
}
