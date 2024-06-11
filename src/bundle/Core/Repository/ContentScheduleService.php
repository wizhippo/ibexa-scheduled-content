<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService as ContentScheduleServiceInterface;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleCreateStruct;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleList;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\ScheduleUpdateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\Handler as ContentScheduleHandler;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;

final class ContentScheduleService implements ContentScheduleServiceInterface
{
    public function __construct(
        private readonly Repository $repository,
        private readonly PermissionResolver $permissionResolver,
        private readonly ContentScheduleHandler $contentScheduleHandler,
        private readonly ContentScheduleMapper $mapper
    ) {
    }

    public function loadSchedule(int $scheduleId): Schedule
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'read') === false) {
            throw new UnauthorizedException('wzh_schedule', 'read');
        }

        $spiSchedule = $this->contentScheduleHandler->load($scheduleId);

        return $this->mapper->buildScheduleDomainObject($spiSchedule);
    }

    public function loadSchedulesByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): ScheduleList {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'read') === false) {
            throw new UnauthorizedException('wzh_schedule', 'read');
        }

        $spiSchedules = $this->contentScheduleHandler->loadSchedulesByContentId($contentId, $offset, $limit);

        $schedules = [];
        foreach ($spiSchedules as $spiSchedule) {
            $schedules[] = $this->mapper->buildScheduleDomainObject($spiSchedule);
        }

        return new ScheduleList($schedules);
    }

    public function loadSchedulesByContentIdCount(int $contentId): int
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'read') === false) {
            throw new UnauthorizedException('wzh_schedule', 'read');
        }

        return $this->contentScheduleHandler->loadSchedulesByContentIdCount($contentId);
    }

    public function loadSchedulesByNotEvaluated(\DateTime $now): ScheduleList
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'read') === false) {
            throw new UnauthorizedException('wzh_schedule', 'read');
        }

        $spiSchedules = $this->contentScheduleHandler->loadSchedulesByNotEvaluated($now);

        $schedules = [];
        foreach ($spiSchedules as $spiSchedule) {
            $schedules[] = $this->mapper->buildScheduleDomainObject($spiSchedule);
        }

        return new ScheduleList($schedules);
    }

    public function createSchedule(ScheduleCreateStruct $scheduleCreateStruct): Schedule
    {
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'add') === false) {
            throw new UnauthorizedException('wzh_schedule', 'add');
        }

        if (!$scheduleCreateStruct->contentId) {
            throw new InvalidArgumentValue('contentId', $scheduleCreateStruct->contentId, ScheduleCreateStruct::class);
        }

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
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'add') === false) {
            throw new UnauthorizedException('wzh_schedule', 'add');
        }

        $updateStruct = new UpdateStruct();
        $updateStruct->eventDateTime =
            ($scheduleUpdateStruct->eventDateTime ?? $schedule->eventDateTime)
                ->getTimestamp()
        ;
        $updateStruct->eventAction = $scheduleUpdateStruct->eventAction ?? $schedule->eventAction;
        $updateStruct->remark = $scheduleUpdateStruct->remark ?? $schedule->remark;
        $updateStruct->evaluatedDateTime = $scheduleUpdateStruct->evaluatedDateTime ?? $schedule->evaluatedDateTime;

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
        if ($this->permissionResolver->hasAccess('wzh_schedule', 'delete') === false) {
            throw new UnauthorizedException('wzh_schedule', 'delete');
        }

        $this->repository->beginTransaction();

        try {
            $this->contentScheduleHandler->deleteSchedule($schedule->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();

            throw $e;
        }
    }

    public function evaluateSchedule(Schedule $schedule): void
    {
        $this->repository->beginTransaction();

        try {
            $this->contentScheduleHandler->evaluate($schedule->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();

            throw $e;
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
}
