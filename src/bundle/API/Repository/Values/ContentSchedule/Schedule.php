<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @property-read int $id Schedule ID
 * @property-read int $contentId Content ID
 * @property-read ?int $versionNo Version No
 * @property-read DateTimeImmutable $eventDateTime Event effect datetime
 * @property-read string $eventAction Name of the action to perform
 */
class Schedule extends ValueObject
{
    public const ACTION_PUBLISH = 1;
    public const ACTION_HIDE = 2;

    protected int $id;

    protected int $contentId;

    protected ?int $versionNo = null;

    protected DateTimeImmutable $eventDateTime;

    protected string $eventAction;
}
