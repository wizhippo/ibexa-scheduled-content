<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\CreateStruct;
use Wizhippo\ScheduledContentBundle\SPI\Persistence\ContentSchedule\UpdateStruct;
use function count;
use function is_array;
use const PHP_INT_MAX;

final class DoctrineDatabase extends Gateway
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function getScheduleData(int $scheduleId): array
    {
        $query = $this->createContentSchedulesQueryBuilder();
        $query->where(
            $query->expr()->eq('id', ':id')
        )->setParameter('id', $scheduleId, Types::INTEGER);

        $row = $query->execute()->fetchAssociative();

        if (is_array($row)) {
            return $row;
        }

        throw new NotFoundException('content schedule', $scheduleId);
    }

    public function getSchedulesData(
        bool $includeEvaluated,
        int $offset = 0,
        int $limit = -1
    ): array {
        $queryBuilder = $this->createContentSchedulesQueryBuilder()
            ->setFirstResult($offset)
            ->setMaxResults($limit > 0 ? $limit : PHP_INT_MAX)
        ;

        $criteria = [];

        if (!$includeEvaluated) {
            $criteria[] = $queryBuilder->expr()->isNull('evaluated_date_time');
        }

        if ($criteria) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->and(...$criteria)
            );
        }

        $rows = $queryBuilder->execute()->fetchAllAssociative();

        if (count($rows) === 0) {
            return [];
        }

        return $rows;
    }

    public function getSchedulesDataCount(bool $includeEvaluated): int
    {
        $queryBuilder = $this->createContentSchedulesQueryBuilder()
            ->resetQueryPart('orderBy')
            ->select(
                $this->connection->getDatabasePlatform()
                    ->getCountExpression('DISTINCT wzh_scheduled_content.id').' AS count'
            )
        ;

        $criteria = [];

        if (!$includeEvaluated) {
            $criteria[] = $queryBuilder->expr()->isNull('evaluated_date_time');
        }

        if ($criteria) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->and(...$criteria)
            );
        }

        return (int)$queryBuilder->execute()->fetchOne();
    }

    public function getSchedulesDataByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): array {
        $queryBuilder = $this->createContentSchedulesByContentIdQueryBuilder($contentId)
            ->setFirstResult($offset)
            ->setMaxResults($limit > 0 ? $limit : PHP_INT_MAX)
        ;

        $rows = $queryBuilder->execute()->fetchAllAssociative();

        if (count($rows) === 0) {
            return [];
        }

        return $rows;
    }

    public function getSchedulesDataByContentIdCount(int $contentId): int
    {
        $queryBuilder = $this->createContentSchedulesByContentIdQueryBuilder($contentId)
            ->resetQueryPart('orderBy')
            ->select(
                $this->connection->getDatabasePlatform()
                    ->getCountExpression('DISTINCT wzh_scheduled_content.id').' AS count'
            )
        ;

        return (int)$queryBuilder->execute()->fetchOne();
    }

    public function create(CreateStruct $createStruct): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert('wzh_scheduled_content')
            ->setValue(
                'content_id',
                ':content_id'
            )
            ->setValue(
                'event_date_time',
                ':event_date_time'
            )
            ->setValue(
                'event_action',
                ':event_action'
            )
            ->setValue(
                'remark',
                ':remark'
            )
            ->setParameter('content_id', $createStruct->contentId, Types::INTEGER)
            ->setParameter('event_date_time', $createStruct->eventDateTime, Types::INTEGER)
            ->setParameter('event_action', $createStruct->eventAction, Types::STRING)
            ->setParameter('remark', $createStruct->remark, Types::STRING)
        ;

        $query->execute();

        return (int)$this->connection->lastInsertId();
    }

    public function update(UpdateStruct $updateStruct, int $scheduleId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update('wzh_scheduled_content')
            ->set(
                'event_date_time',
                ':event_date_time'
            )
            ->set(
                'event_action',
                ':event_action'
            )
            ->set(
                'remark',
                ':remark'
            )
            ->where(
                $query->expr()->eq(
                    'id',
                    ':id'
                )
            )
            ->setParameter('id', $scheduleId, Types::INTEGER)
            ->setParameter('event_date_time', $updateStruct->eventDateTime, Types::INTEGER)
            ->setParameter('event_action', $updateStruct->eventAction, Types::STRING)
            ->setParameter('remark', $updateStruct->remark, Types::STRING)
        ;

        $query->execute();
    }

    public function deleteSchedule(int $scheduleId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete('wzh_scheduled_content')
            ->where(
                $query->expr()->eq(
                    'id',
                    ':schedule_id'
                )
            )->setParameter('schedule_id', $scheduleId, Types::INTEGER)
        ;

        $query->execute();
    }

    public function evaluate(int $scheduleId): bool
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update('wzh_scheduled_content')
            ->set(
                'evaluated_date_time',
                ':evaluated_date_time'
            )
            ->where(
                $query->expr()->eq(
                    'id',
                    ':id'
                )
            )
            ->setParameter('id', $scheduleId, Types::INTEGER)
            ->setParameter('evaluated_date_time', (new \DateTimeImmutable())->getTimestamp(), Types::INTEGER)
        ;

        return $query->execute() === 1;
    }

    /**
     * Returns a list of schedules to be evaluated in the order to be actioned
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSchedulesDataByNeedEvaluation(
        \DateTimeImmutable $now,
        int $offset = 0,
        int $limit = -1
    ): array {
        $queryBuilder = $this->createContentSchedulesByNeedsEvaluationQueryBuilder($now)
            ->setFirstResult($offset)
            ->setMaxResults($limit > 0 ? $limit : PHP_INT_MAX)
        ;

        $rows = $queryBuilder->execute()->fetchAllAssociative();

        if (count($rows) === 0) {
            return [];
        }

        return $rows;
    }

    public function getSchedulesDataByNeedEvaluationCount(\DateTimeImmutable $now): int
    {
        $queryBuilder = $this->createContentSchedulesByNeedsEvaluationQueryBuilder($now)
            ->resetQueryPart('orderBy')
            ->select(
                $this->connection->getDatabasePlatform()
                    ->getCountExpression('DISTINCT wzh_scheduled_content.id').' AS count'
            )
        ;

        return (int)$queryBuilder->execute()->fetchOne();
    }


    private function createContentSchedulesQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('wzh_scheduled_content')
            ->orderBy('event_date_time', 'ASC')
        ;
    }

    private function createContentSchedulesByNeedsEvaluationQueryBuilder(\DateTimeImmutable $now): QueryBuilder
    {
        return $this->createContentSchedulesQueryBuilder()
            ->andWhere('evaluated_date_time is null')
            ->andWhere('event_date_time <= :now')
            ->setParameter('now', $now->getTimestamp())
        ;
    }

    private function createContentSchedulesByContentIdQueryBuilder(int $contentId): QueryBuilder
    {
        $query = $this->createContentSchedulesQueryBuilder();
        $query
            ->andWhere(
                $query->expr()->eq('content_id', ':content_id')
            )->setParameter('content_id', $contentId, Types::INTEGER)
        ;

        return $query;
    }
}
