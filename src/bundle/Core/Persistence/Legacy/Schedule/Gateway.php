<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule;

use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;

abstract class Gateway
{
    abstract public function getScheduleData(int $scheduleId): array;

    abstract public function getSchedulesData(
        bool $includeEvaluated,
        int $offset = 0,
        int $limit = -1
    ): array;

    abstract public function getSchedulesDataCount(bool $includeEvaluated): int;

    abstract public function getSchedulesDataByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): array;

    abstract public function getSchedulesDataByContentIdCount(int $contentId): int;

    abstract public function getSchedulesDataByNeedEvaluation(
        \DateTimeImmutable $now,
        int $offset = 0,
        int $limit = -1
    ): array;

    abstract public function getSchedulesDataByNeedEvaluationCount(\DateTimeImmutable $now): int;

    abstract public function create(CreateStruct $createStruct): int;

    abstract public function update(UpdateStruct $updateStruct, int $scheduleId): void;

    abstract public function deleteSchedule(int $scheduleId): void;

    abstract public function evaluate(int $scheduleId): bool;
}
