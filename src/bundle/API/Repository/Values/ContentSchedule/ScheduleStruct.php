<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule;

use DateTimeInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

abstract class ScheduleStruct extends ValueObject
{
    public DateTimeInterface $eventDateTime;

    public string $eventAction;

    public ?string $remark = null;
}
