<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class ScheduleStruct extends ValueObject
{
    public DateTimeImmutable $eventDateTime;

    public string $eventAction;

    public ?string $remark = null;
}
