<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule;

interface Handler
{
    public function load(int $scheduleId): Schedule;

    /**
     * @return Schedule[]
     */
    public function loadSchedulesByContentId(int $contentId, int $offset = 0, int $limit = -1): array;

    public function loadSchedulesByContentIdCount(int $contentId): int;

    public function loadSchedulesByNotEvaluated(\DateTime $now): array;

    public function create(CreateStruct $createStruct): Schedule;

    public function update(UpdateStruct $updateStruct, int $scheduleId): Schedule;

    public function deleteSchedule(int $scheduleId): void;

    public function evaluate(int $scheduleId): void;
}
