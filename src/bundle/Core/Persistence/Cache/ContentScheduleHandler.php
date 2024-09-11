<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Cache;

use Ibexa\Core\Persistence\Cache\AbstractInMemoryHandler;
use Ibexa\Core\Persistence\Cache\InMemory\InMemoryCache;
use Ibexa\Core\Persistence\Cache\PersistenceLogger;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Handler as ContentScheduleHandlerInterface;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;

class ContentScheduleHandler extends AbstractInMemoryHandler implements ContentScheduleHandlerInterface
{
    private ContentScheduleHandlerInterface $contentScheduleHandler;

    public function __construct(
        TagAwareAdapterInterface $cache,
        PersistenceLogger $logger,
        InMemoryCache $inMemory,
        ContentScheduleHandlerInterface $contentScheduleHandler
    ) {
        parent::__construct($cache, $logger, $inMemory);
        $this->contentScheduleHandler = $contentScheduleHandler;
    }

    public function load(int $scheduleId): Schedule
    {
        /** @var Schedule $cacheValue */
        $cacheValue = $this->getCacheValue(
            $scheduleId,
            'wzh-content-schedule-',
            function (int $scheduleId): Schedule {
                return $this->contentScheduleHandler->load($scheduleId);
            },
            static function (Schedule $schedule): array {
                return ['schedule-'.$schedule->id];
            },
            static function (Schedule $schedule): array {
                return ['wzh-content-schedule-'.$schedule->id];
            }
        );

        return $cacheValue;
    }

    public function loadSchedules(
        bool $includeEvaluated,
        int $offset = 0,
        int $limit = -1
    ): array {
        return $this->contentScheduleHandler->loadSchedules($includeEvaluated, $offset, $limit);
    }

    public function loadSchedulesCount(bool $includeEvaluated): int
    {
        return $this->contentScheduleHandler->loadSchedulesCount($includeEvaluated);
    }

    public function loadSchedulesByContentId(int $contentId, int $offset = 0, int $limit = -1): array
    {
        return $this->contentScheduleHandler->loadSchedulesByContentId($contentId, $offset, $limit);
    }

    public function loadSchedulesByContentIdCount(int $contentId): int
    {
        return $this->contentScheduleHandler->loadSchedulesByContentIdCount($contentId);
    }

    public function loadSchedulesByNeedEvaluation(\DateTimeImmutable $now, int $offset = 0, int $limit = -1): array
    {
        return $this->contentScheduleHandler->loadSchedulesByNeedEvaluation($now, $offset, $limit);
    }

    public function loadSchedulesByNeedEvaluationCount(\DateTimeImmutable $now): int
    {
        return $this->contentScheduleHandler->loadSchedulesByNeedEvaluationCount($now);
    }

    public function create(CreateStruct $createStruct): Schedule
    {
        return $this->contentScheduleHandler->create($createStruct);
    }

    public function update(UpdateStruct $updateStruct, int $scheduleId): Schedule
    {
        $updatedSchedule = $this->contentScheduleHandler->update($updateStruct, $scheduleId);
        $this->cache->invalidateTags(['content-schedule-'.$scheduleId]);

        return $updatedSchedule;
    }

    public function deleteSchedule(int $scheduleId): void
    {
        $this->contentScheduleHandler->deleteSchedule($scheduleId);
        $this->cache->invalidateTags(['wzh-content-schedule-'.$scheduleId]);
    }

    public function evaluate(int $scheduleId): bool
    {
        $ret = $this->contentScheduleHandler->evaluate($scheduleId);
        $this->cache->invalidateTags(['wzh-content-schedule-'.$scheduleId]);

        return $ret;
    }
}
