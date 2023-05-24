<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule;

use DateTimeInterface;
use Ibexa\Contracts\Core\Persistence\ValueObject;

final class CreateStruct extends ValueObject
{
    public int $contentId;

    public int $eventDateTime;

    public string $eventAction;

    public ?string $remark;
}
