<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;

class ContentHideLaterData
{
    public function __construct(
        private ?VersionInfo $versionInfo = null,
        private ?\DateTime $hideDateTime = null,
    ) {
        $this->hideDateTime = $this->hideDateTime ?? new \DateTime();
    }

    public function getVersionInfo(): ?versionInfo
    {
        return $this->versionInfo;
    }

    public function setVersionInfo(?VersionInfo $versionInfo): void
    {
        $this->versionInfo = $versionInfo;
    }

    public function getHideDateTime(): ?\DateTime
    {
        return $this->hideDateTime;
    }

    public function setHideDateTime(?\DateTime $hideDateTime): void
    {
        $this->hideDateTime = $hideDateTime;
    }
}
