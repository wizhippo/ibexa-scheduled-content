<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule;

use Doctrine\Common\Collections\ArrayCollection;
use function array_filter;
use function array_map;

final class ScheduleList extends ArrayCollection
{
    public function __construct(array $schedules = [])
    {
        parent::__construct(
            array_filter(
                $schedules,
                static function (Schedule $schedule): bool {
                    return true;
                }
            )
        );
    }

    /**
     * @return Schedule[]|array
     */
    public function getSchedules(): array
    {
        return $this->toArray();
    }

    public function getScheduleIds(): array
    {
        return array_map(
            static function (Schedule $schedule): int {
                return (int)$schedule->id;
            },
            $this->getSchedules()
        );
    }
}
