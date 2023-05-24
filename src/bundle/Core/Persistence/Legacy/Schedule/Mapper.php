<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule;

use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Schedule;
use function array_values;

class Mapper
{
    public function createScheduleFromRow(array $row): Schedule
    {
        $schedule = new Schedule();

        $schedule->id = (int)$row['id'];
        $schedule->contentId = (int)$row['content_id'];
        $schedule->eventDateTime = (int)$row['event_date_time'];
        $schedule->eventAction = (string)$row['event_action'];
        $schedule->remark = $row['remark'];
        $schedule->evaluated = (bool)$row['evaluated'];

        return $schedule;
    }

    public function extractScheduleListFromRows(array $rows): array
    {
        $scheduleList = [];
        foreach ($rows as $row) {
            $scheduleId = (int)$row['id'];
            if (!isset($scheduleList[$scheduleId])) {
                $scheduleList[$scheduleId] = $this->createScheduleFromRow($row);
            }
        }

        return array_values($scheduleList);
    }
}
