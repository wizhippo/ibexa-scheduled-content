<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService as ContentScheduleServiceInterface;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleCreateStruct;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleList;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleUpdateStruct;
use Wizhippo\ScheduledContentBundle\Exception\EventActionConflictException;
use Wizhippo\ScheduledContentBundle\Exception\EventOutOfOrderException;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Handler as ContentScheduleHandler;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Schedule as SPISchedule;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;

final class ContentScheduleService implements ContentScheduleServiceInterface
{
    public function __construct(
        private readonly Repository $repository,
        private readonly PermissionResolver $permissionResolver,
        private readonly ContentScheduleHandler $contentScheduleHandler,
        private readonly ContentScheduleMapper $mapper,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    private function checkReadPermission(): void
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'read') === false) {
            throw new UnauthorizedException('wzh_schedule', 'read');
        }
    }

    private function checkAddPermission(): void
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'add') === false) {
            throw new UnauthorizedException('wzh_schedule', 'add');
        }
    }

    private function checkDeletePermission(): void
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'delete') === false) {
            throw new UnauthorizedException('wzh_schedule', 'delete');
        }
    }

    private function mapSchedules(array $spiSchedules): array
    {
        return array_map(fn ($spiSchedule) => $this->mapper->buildScheduleDomainObject($spiSchedule), $spiSchedules);
    }

    public function loadSchedule(int $scheduleId): Schedule
    {
        $this->checkReadPermission();
        $spiSchedule = $this->contentScheduleHandler->load($scheduleId);

        return $this->mapper->buildScheduleDomainObject($spiSchedule);
    }

    public function loadSchedules(
        bool $includeEvaluated,
        int $offset = 0,
        int $limit = -1
    ): ScheduleList {
        $this->checkReadPermission();
        $spiSchedules = $this->contentScheduleHandler->loadSchedules($includeEvaluated, $offset, $limit);

        return new ScheduleList($this->mapSchedules($spiSchedules));
    }

    public function loadSchedulesCount(bool $includeEvaluated): int
    {
        $this->checkReadPermission();

        return $this->contentScheduleHandler->loadSchedulesCount($includeEvaluated);
    }

    public function loadSchedulesByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): ScheduleList {
        $this->checkReadPermission();
        $spiSchedules = $this->contentScheduleHandler->loadSchedulesByContentId($contentId, $offset, $limit);

        return new ScheduleList($this->mapSchedules($spiSchedules));
    }

    public function loadSchedulesByContentIdCount(int $contentId): int
    {
        $this->checkReadPermission();

        return $this->contentScheduleHandler->loadSchedulesByContentIdCount($contentId);
    }

    public function loadSchedulesByNeedEvaluation(
        \DateTimeImmutable $now,
        int $offset = 0,
        int $limit = -1
    ): ScheduleList {
        $this->checkReadPermission();
        $spiSchedules = $this->contentScheduleHandler->loadSchedulesByNeedEvaluation($now, $offset, $limit);

        return new ScheduleList($this->mapSchedules($spiSchedules));
    }

    public function loadSchedulesByNeedEvaluationCount(\DateTimeImmutable $now): int
    {
        $this->checkReadPermission();

        return $this->contentScheduleHandler->loadSchedulesByNeedEvaluationCount($now);
    }

    public function createSchedule(ScheduleCreateStruct $scheduleCreateStruct): Schedule
    {
        $this->checkAddPermission();

        if (!$scheduleCreateStruct->contentId) {
            throw new InvalidArgumentValue('contentId', $scheduleCreateStruct->contentId, ScheduleCreateStruct::class);
        }

        $now = new \DateTimeImmutable();
        if ($scheduleCreateStruct->eventDateTime < $now) {
            throw new EventOutOfOrderException(
                "Event date cannot be in the past '{$now->format('Y-m-d H:i:s')}' < '{$scheduleCreateStruct->eventDateTime->format('Y-m-d H:i:s')}'"
            );
        }

        $this->validateScheduleCreateStruct($scheduleCreateStruct);

        $createStruct = new CreateStruct();
        $createStruct->contentId = $scheduleCreateStruct->contentId;
        $createStruct->eventDateTime = $scheduleCreateStruct->eventDateTime->getTimestamp();
        $createStruct->eventAction = $scheduleCreateStruct->eventAction;
        $createStruct->remark = $scheduleCreateStruct->remark;

        $this->repository->beginTransaction();

        try {
            $newSchedule = $this->contentScheduleHandler->create($createStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->mapper->buildScheduleDomainObject($newSchedule);
    }

    public function updateSchedule(Schedule $schedule, ScheduleUpdateStruct $scheduleUpdateStruct): Schedule
    {
        $this->checkAddPermission();

        $updateStruct = new UpdateStruct();
        $updateStruct->eventDateTime =
            ($scheduleUpdateStruct->eventDateTime ?? $schedule->eventDateTime)
                ->getTimestamp()
        ;
        $updateStruct->eventAction = $scheduleUpdateStruct->eventAction ?? $schedule->eventAction;
        $updateStruct->remark = $scheduleUpdateStruct->remark ?? $schedule->remark;
        $updateStruct->evaluatedDateTime = $scheduleUpdateStruct->evaluatedDateTime ?? $schedule->evaluatedDateTime;

        $this->validateScheduleUpdateStruct($schedule, $updateStruct);

        $this->repository->beginTransaction();

        try {
            $updatedSchedule = $this->contentScheduleHandler->update($updateStruct, $schedule->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->mapper->buildScheduleDomainObject($updatedSchedule);
    }

    public function deleteSchedule(Schedule $schedule): void
    {
        $this->checkDeletePermission();
        $this->validateDeletableSchedule($schedule);

        $this->repository->beginTransaction();

        try {
            $this->contentScheduleHandler->deleteSchedule($schedule->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    public function evaluateSchedule(Schedule $schedule, bool $isDryRun): void
    {
        $this->repository->beginTransaction();

        try {
            $contentInfo = $this->repository->getContentService()->loadContentInfo($schedule->contentId);
            if ($contentInfo->status !== ContentInfo::STATUS_PUBLISHED) {
                $msg = "Content {$contentInfo->id} for schedule {$schedule->id} is not published taking no action";
                $this->logger->info($msg);
            } else {
                if ($schedule->eventAction === Schedule::ACTION_HIDE && !$contentInfo->isHidden) {
                    if (!$isDryRun) {
                        $this->repository->getContentService()->hideContent($contentInfo);
                    }
                    $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Hide";
                    $this->logger->info($msg);
                } elseif ($schedule->eventAction === Schedule::ACTION_SHOW && $contentInfo->isHidden) {
                    if (!$isDryRun) {
                        $this->repository->getContentService()->revealContent($contentInfo);
                    }
                    $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Show";
                    $this->logger->info($msg);
                } elseif ($schedule->eventAction === Schedule::ACTION_TRASH) {
                    if (!$isDryRun) {
                        $this->repository->getTrashService()->trash($contentInfo->getMainLocation());
                    }
                    $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Trash";
                    $this->logger->info($msg);
                }
            }

            if (!$isDryRun) {
                $this->contentScheduleHandler->evaluate($schedule->id);
            }

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    public function evaluateSchedulesByNeedEvaluation(\DateTimeImmutable $now, bool $isDryRun): void
    {
        /** @var Schedule $schedule */
        foreach ($this->loadSchedulesByNeedEvaluation($now) as $schedule) {
            if (!$isDryRun) {
                $this->evaluateSchedule($schedule, $isDryRun);
            }
            $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Evaluated";
            $this->logger->info($msg);
        }
    }

    public function newScheduleCreateStruct(): ScheduleCreateStruct
    {
        return new ScheduleCreateStruct();
    }

    public function newScheduleUpdateStruct(): ScheduleUpdateStruct
    {
        return new ScheduleUpdateStruct();
    }

    private function validateDeletableSchedule(Schedule $schedule): void
    {
        $spiSchedules = $this->contentScheduleHandler->loadSchedulesByContentId($schedule->contentId);

        $previousSchedule = null;
        $nextSchedule = null;

        foreach ($spiSchedules as $spiSchedule) {
            if ($spiSchedule->id === $schedule->id) {
                continue;
            }

            if ($spiSchedule->eventDateTime < $schedule->eventDateTime->getTimestamp()) {
                if ($previousSchedule === null || $spiSchedule->eventDateTime > $previousSchedule->eventDateTime) {
                    $previousSchedule = $spiSchedule;
                }
            }

            if ($spiSchedule->eventDateTime > $schedule->eventDateTime->getTimestamp()) {
                if ($nextSchedule === null || $spiSchedule->eventDateTime < $nextSchedule->eventDateTime) {
                    $nextSchedule = $spiSchedule;
                }
            }
        }

        if ($previousSchedule && $nextSchedule) {
            switch ($previousSchedule->eventAction) {
                case $nextSchedule->eventAction:
                    throw new EventActionConflictException(
                        "Previous and next schedule actions conflict '$previousSchedule->eventAction' -> '$nextSchedule->eventAction'"
                    );
                    break;
                case Schedule::ACTION_SHOW:
                    if ($nextSchedule->eventAction === Schedule::ACTION_SHOW) {
                        throw new EventActionConflictException(
                            "Previous and next schedule actions conflict '$previousSchedule->eventAction' -> '$nextSchedule->eventAction'"
                        );
                    }
                    break;
                case Schedule::ACTION_HIDE:
                    if ($nextSchedule->eventAction === Schedule::ACTION_HIDE) {
                        throw new EventActionConflictException(
                            "Previous and next schedule actions conflict '$previousSchedule->eventAction' -> '$nextSchedule->eventAction'"
                        );
                    }
                    break;
            }
        }
    }

    private function validateScheduleUpdateStruct(
        Schedule $schedule,
        UpdateStruct $updateStruct
    ): void {
        $spiSchedules = $this->contentScheduleHandler->loadSchedulesByContentId($schedule->contentId);

        $previousSchedule = null;
        $nextSchedule = null;

        foreach ($spiSchedules as $spiSchedule) {
            if ($spiSchedule->id === $schedule->id) {
                continue;
            }

            if ($spiSchedule->eventDateTime < $updateStruct->eventDateTime) {
                if ($previousSchedule === null || $spiSchedule->eventDateTime > $previousSchedule->eventDateTime) {
                    $previousSchedule = $spiSchedule;
                }
            }

            if ($spiSchedule->eventDateTime > $updateStruct->eventDateTime) {
                if ($nextSchedule === null || $spiSchedule->eventDateTime < $nextSchedule->eventDateTime) {
                    $nextSchedule = $spiSchedule;
                }
            }
        }

        if ($previousSchedule) {
            if ($previousSchedule->eventAction === $updateStruct->eventAction) {
                throw new EventActionConflictException('Previous schedule action is the same as the updated action.');
            }

            switch ($updateStruct->eventAction) {
                case Schedule::ACTION_SHOW:
                    if ($previousSchedule->eventAction !== Schedule::ACTION_HIDE) {
                        throw new EventActionConflictException(
                            'Previous schedule action conflicts with updated action.'
                        );
                    }
                    break;
                case Schedule::ACTION_HIDE:
                    if ($previousSchedule->eventAction !== Schedule::ACTION_SHOW) {
                        throw new EventActionConflictException(
                            'Previous schedule action conflicts with updated action.'
                        );
                    }
                    break;
            }
        }

        if ($nextSchedule) {
            if ($nextSchedule->eventAction === $updateStruct->eventAction) {
                throw new EventActionConflictException('Next schedule action is the same as the updated action.');
            }

            switch ($updateStruct->eventAction) {
                case Schedule::ACTION_SHOW:
                    if ($nextSchedule->eventAction !== Schedule::ACTION_HIDE) {
                        throw new EventActionConflictException('Next schedule action conflicts with updated action.');
                    }
                    break;
                case Schedule::ACTION_HIDE:
                    if ($nextSchedule->eventAction !== Schedule::ACTION_SHOW) {
                        throw new EventActionConflictException('Next schedule action conflicts with updated action.');
                    }
                    break;
            }
        }
    }

    private function validateScheduleCreateStruct(ScheduleCreateStruct $schedule): void
    {
        $previousSchedules = $this->contentScheduleHandler->loadSchedulesByContentId($schedule->contentId);
        if (count($previousSchedules)) {
            /** @var SPISchedule $previousSchedule */
            $previousSchedule = end($previousSchedules);

            if ($previousSchedule->eventDateTime >= $schedule->eventDateTime->getTimestamp()) {
                throw new EventOutOfOrderException('Event date out of order');
            }

            if ($previousSchedule->eventAction === $schedule->eventAction) {
                throw new EventActionConflictException('Previous schedule action is same as wanted action');
            }
            switch ($schedule->eventAction) {
                case Schedule::ACTION_SHOW:
                    if ($previousSchedule->eventAction !== Schedule::ACTION_HIDE) {
                        throw new EventActionConflictException(
                            'Previous schedule action conflicts with wanted action'
                        );
                    }
                    break;
                case Schedule::ACTION_HIDE:
                    if ($previousSchedule->eventAction !== Schedule::ACTION_SHOW) {
                        throw new EventActionConflictException(
                            'Previous schedule action conflicts with wanted action'
                        );
                    }
                    break;
            }
        }
    }

//    protected function getPendingScheduleInfoWithMostRecentMarked(\DateTime $now): \Traversable
//    {
//        $query = $this->connection->createQueryBuilder()
//            ->select('content_id, id')
//            ->addSelect(
//                "CASE
//                WHEN ROW_NUMBER() OVER (PARTITION BY content_id ORDER BY event_date_time DESC) = 1 THEN 1
//                ELSE 0
//              END AS is_first_row"
//            )
//            ->from('wzh_scheduled_content')
//            ->where('event_date_time <= :now')
//            ->andWhere('evaluated is NULL')
//            ->orderBy('content_id')
//            ->addOrderBy('event_date_time', 'DESC')
//            ->setParameter('now', $now->getTimestamp())
//        ;
//
//        return $query->execute()->iterateAssociative();
//    }
}
