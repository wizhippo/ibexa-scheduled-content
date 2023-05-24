<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location;

class ContentScheduleUpdateData
{
    public function __construct(
        private ?int $id = null,
        private ?\DateTime $eventDateTime = null,
        private ?string $eventAction = null,
        private ?string $remark = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
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
