<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule;

use Ibexa\Contracts\Core\Persistence\ValueObject;

final class UpdateStruct extends ValueObject
{
    public int $eventDateTime;

    public string $eventAction;

    public ?string $remark;

    public ?int $evaluatedDateTime;
}
