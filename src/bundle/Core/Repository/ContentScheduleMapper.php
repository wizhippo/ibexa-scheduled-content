<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Repository;

use DateTime;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Schedule as SPISchedule;

final class ContentScheduleMapper
{
    public function buildScheduleDomainObject(SPISchedule $spiSchedule): Schedule
    {
        return $this->buildScheduleDomainList([$spiSchedule])[$spiSchedule->id];
    }

    public function buildScheduleDomainList(array $spiSchedules): array
    {
        $schedules = [];
        foreach ($spiSchedules as $spiSchedule) {
            $schedules[$spiSchedule->id] = new Schedule([
                'id' => $spiSchedule->id,
                'contentId' => $spiSchedule->contentId,
                'eventDateTime' => $this->getDateTime($spiSchedule->eventDateTime),
                'eventAction' => $spiSchedule->eventAction,
                'remark' => $spiSchedule->remark,
                'evaluatedDateTime' => $spiSchedule->evaluatedDateTime !== null ? $this->getDateTime(
                    $spiSchedule->evaluatedDateTime
                ) : null,
            ]);
        }

        return $schedules;
    }

    /**
     * Returns \DateTime object from given $timestamp in environment timezone.
     *
     * This method is needed because constructing \DateTime with $timestamp will
     * return the object in UTC timezone.
     */
    public function getDateTime($timestamp): DateTime
    {
        $dateTime = new DateTime('now');
        $dateTime->setTimestamp((int)$timestamp);

        return $dateTime;
    }
}
