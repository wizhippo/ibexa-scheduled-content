<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;

class ContentScheduleDeleteData
{
    public function __construct(
        private ?ContentInfo $contentInfo = null,
        private array $schedules = []
    ) {
    }

    public function getContentInfo(): ?ContentInfo
    {
        return $this->contentInfo;
    }

    public function setContentInfo(?ContentInfo $contentInfo): void
    {
        $this->contentInfo = $contentInfo;
    }

    public function getSchedules(): array
    {
        return $this->schedules;
    }

    public function setSchedules(array $schedules): void
    {
        $this->schedules = $schedules;
    }
}
