<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\API\Repository;

use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleCreateStruct;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleList;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleUpdateStruct;

interface ContentScheduleService
{
    public function loadSchedule(int $scheduleId): Schedule;

    public function loadSchedulesByContentId(int $contentId, int $offset = 0, int $limit = -1): ScheduleList;

    public function loadSchedulesByContentIdCount(int $contentId): int;

    public function loadSchedulesByNotEvaluated(\DateTime $now): ScheduleList;

    public function createSchedule(ScheduleCreateStruct $scheduleCreateStruct): Schedule;

    public function updateSchedule(Schedule $schedule, ScheduleUpdateStruct $scheduleUpdateStruct): Schedule;

    public function deleteSchedule(Schedule $schedule): void;

    public function evaluateSchedule(Schedule $schedule): void;

    public function newScheduleCreateStruct(): ScheduleCreateStruct;

    public function newScheduleUpdateStruct(): ScheduleUpdateStruct;
}
