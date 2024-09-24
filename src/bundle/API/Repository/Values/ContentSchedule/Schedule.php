<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @property-read int $id Schedule ID
 * @property-read int $contentId Content ID
 * @property-read DateTimeImmutable $eventDateTime Event effect datetime
 * @property-read string $eventAction Name of the action to perform
 * @property-read string $remark Remark to describe why the schedule
 * @property-read ?DateTimeImmutable $evaluatedDateTime Indicates when processed
 */
class Schedule extends ValueObject
{
    public const ACTION_SHOW = 'show';
    public const ACTION_HIDE = 'hide';
    public const ACTION_TRASH = 'trash';

    protected int $id;

    protected int $contentId;

    protected DateTimeImmutable $eventDateTime;

    protected string $eventAction;

    protected ?string $remark;

    protected ?DateTimeImmutable $evaluatedDateTime;
}
