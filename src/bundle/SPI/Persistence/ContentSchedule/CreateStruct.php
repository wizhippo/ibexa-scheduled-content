<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule;

use Ibexa\Contracts\Core\Persistence\ValueObject;

final class CreateStruct extends ValueObject
{
    public int $contentId;

    public ?int $versionNo = null;

    public int $eventDateTime;

    public string $eventAction;
}
