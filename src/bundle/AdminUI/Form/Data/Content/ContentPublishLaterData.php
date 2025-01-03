<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

class ContentPublishLaterData
{
    public function __construct(
        private ?VersionInfo $versionInfo = null,
        private ?\DateTime $publishDateTime = null,
    ) {
        $this->publishDateTime = $this->publishDateTime ?? new \DateTime();
    }

    public function getVersionInfo(): ?versionInfo
    {
        return $this->versionInfo;
    }

    public function setVersionInfo(?VersionInfo $versionInfo): void
    {
        $this->versionInfo = $versionInfo;
    }

    public function getPublishDateTime(): ?\DateTime
    {
        return $this->publishDateTime;
    }

    public function setPublishDateTime(?\DateTime $publishDateTime): void
    {
        $this->publishDateTime = $publishDateTime;
    }
}
