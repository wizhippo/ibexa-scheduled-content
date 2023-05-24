<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;

class ContentScheduleAddData
{
    public function __construct(
        private ?ContentInfo $contentInfo = null,
        private ?\DateTime $eventDateTime = null,
        private ?string $eventAction = null,
        private ?string $remark = null,
    ) {
        $this->eventDateTime = $this->eventDateTime ?? new \DateTime();
    }

    public function getContentInfo(): ?ContentInfo
    {
        return $this->contentInfo;
    }

    public function setContentInfo(?ContentInfo $contentInfo): void
    {
        $this->contentInfo = $contentInfo;
    }

    public function getEventDateTime(): ?\DateTime
    {
        return $this->eventDateTime;
    }

    public function setEventDateTime(?\DateTime $eventDateTime): void
    {
        $this->eventDateTime = $eventDateTime;
    }

    public function getEventAction(): ?string
    {
        return $this->eventAction;
    }

    public function setEventAction(?string $eventAction): void
    {
        $this->eventAction = $eventAction;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }
}