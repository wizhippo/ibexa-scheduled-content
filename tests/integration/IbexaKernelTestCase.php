<?php

declare(strict_types=1);

namespace Wizhippo\Tests\Integration\Schedule;

use Ibexa\Contracts\Core\Test\IbexaKernelTestCase as BaseIbexaKernelTestCase;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;

abstract class IbexaKernelTestCase extends BaseIbexaKernelTestCase
{
    protected static function getContentScheduleService(): ContentScheduleService
    {
        return self::getServiceByClassName(ContentScheduleService::class);
    }
}
