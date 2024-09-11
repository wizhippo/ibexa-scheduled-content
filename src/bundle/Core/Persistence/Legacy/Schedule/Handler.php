<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Handler as BaseContentScheduleHandler;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;
use function count;

class Handler implements BaseContentScheduleHandler
{
    public function __construct(
        private readonly Gateway $gateway,
        private readonly Mapper $mapper
    ) {
    }

    public function load(int $scheduleId): Schedule
    {
        $row = $this->gateway->getScheduleData($scheduleId);
        if (count($row) === 0) {
            throw new NotFoundException('schedule', $scheduleId);
        }

        return $this->mapper->createScheduleFromRow($row);
    }

    public function loadSchedules(
        bool $includeEvaluated,
        int $offset = 0,
        int $limit = -1
    ): array {
        $schedules = $this->gateway->getSchedulesData($includeEvaluated, $offset, $limit);

        return $this->mapper->extractScheduleListFromRows($schedules);
    }

    public function loadSchedulesCount(bool $includeEvaluated): int
    {
        return $this->gateway->getSchedulesDataCount($includeEvaluated);
    }

    public function loadSchedulesByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): array {
        $schedules = $this->gateway->getSchedulesDataByContentId($contentId, $offset, $limit);

        return $this->mapper->extractScheduleListFromRows($schedules);
    }

    public function loadSchedulesByContentIdCount(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): int {
        return $this->gateway->getSchedulesDataByContentIdCount($contentId);
    }

    public function loadSchedulesByNeedEvaluation(
        \DateTimeImmutable $now,
        int $offset = 0,
        int $limit = -1
    ): array {
        $schedules = $this->gateway->getSchedulesDataByNeedEvaluation($now, $offset, $limit);

        return $this->mapper->extractScheduleListFromRows($schedules);
    }

    public function loadSchedulesByNeedEvaluationCount(\DateTimeImmutable $now): int
    {
        return $this->gateway->getSchedulesDataByNeedEvaluationCount($now);
    }

    public function create(CreateStruct $createStruct): Schedule
    {
        $newScheduleId = $this->gateway->create($createStruct);

        return $this->load($newScheduleId);
    }

    public function update(UpdateStruct $updateStruct, int $scheduleId): Schedule
    {
        $this->gateway->update($updateStruct, $scheduleId);

        return $this->load($scheduleId);
    }

    public function deleteSchedule(int $scheduleId): void
    {
        $this->gateway->deleteSchedule($scheduleId);
    }

    public function evaluate(int $scheduleId): bool
    {
        return $this->gateway->evaluate($scheduleId);
    }
}
