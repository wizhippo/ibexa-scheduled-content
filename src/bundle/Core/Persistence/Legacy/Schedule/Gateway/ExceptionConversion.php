<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway;

use Doctrine\DBAL\DBALException;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use PDOException;
use Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;

final class ExceptionConversion extends Gateway
{
    public function __construct(
        private readonly Gateway $innerGateway
    ) {
    }

    public function getScheduleData(int $scheduleId): array
    {
        try {
            return $this->innerGateway->getScheduleData($scheduleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSchedulesData(
        bool $includeEvaluated,
        int $offset = 0,
        int $limit = -1
    ): array {
        try {
            return $this->innerGateway->getSchedulesData($includeEvaluated, $offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSchedulesDataCount(bool $includeEvaluated): int
    {
        try {
            return $this->innerGateway->getSchedulesDataCount($includeEvaluated);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSchedulesDataByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): array {
        try {
            return $this->innerGateway->getSchedulesDataByContentId($contentId, $offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSchedulesDataByContentIdCount(
        int $contentId
    ): int {
        try {
            return $this->innerGateway->getSchedulesDataByContentIdCount($contentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function create(CreateStruct $createStruct): int
    {
        try {
            return $this->innerGateway->create($createStruct);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function update(UpdateStruct $updateStruct, int $scheduleId): void
    {
        try {
            $this->innerGateway->update($updateStruct, $scheduleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteSchedule(int $scheduleId): void
    {
        try {
            $this->innerGateway->deleteSchedule($scheduleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function evaluate(int $scheduleId): bool
    {
        try {
            return $this->innerGateway->evaluate($scheduleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSchedulesDataByNeedEvaluation(
        \DateTimeImmutable $now,
        int $offset = 0,
        int $limit = -1
    ): array {
        try {
            return $this->innerGateway->getSchedulesDataByNeedEvaluation($now, $offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function getSchedulesDataByNeedEvaluationCount(\DateTimeImmutable $now): int
    {
        try {
            return $this->innerGateway->getSchedulesDataByNeedEvaluationCount($now);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
