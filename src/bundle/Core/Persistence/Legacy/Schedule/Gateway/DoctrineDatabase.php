<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Core\Persistence\Legacy\Schedule\Gateway;

use Doctrine\DBAL\Connection;
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
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('*')
            ->from('wzh_scheduled_content')
            ->where(
                $query->expr()->eq('id', ':id')
            )->setParameter('id', $scheduleId, Types::INTEGER)
        ;

        $row = $query->execute()->fetchAssociative();

        if (is_array($row)) {
            return $row;
        }

        throw new NotFoundException('content schedule', $scheduleId);
    }

    public function getSchedulesDataByContentId(
        int $contentId,
        int $offset = 0,
        int $limit = -1
    ): array {
        $queryBuilder = $this->createContentScheduletByContentIdQueryBuilder($contentId)
            ->orderBy('wzh_scheduled_content.event_date_time', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit > 0 ? $limit : PHP_INT_MAX)
        ;

        $rows = $queryBuilder->execute()->fetchAllAssociative();

        if (count($rows) === 0) {
            return [];
        }

        return $rows;
    }

    public function getSchedulesDataByContentIdCount(
        int $contentId
    ): int {
        $queryBuilder = $this->createContentScheduletByContentIdQueryBuilder($contentId)
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

    public function evaluate(int $scheduleId)
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

        $query->execute();
    }

    /**
     * Returns a list of schedules to be evaluated in the order to be actioned
     *
     * @param \DateTime $now
     * @return \Traversable
     * @throws \Doctrine\DBAL\Exception
     */
    public function getSchedulesDataByNotEvaluated(\DateTime $now): array
    {
        $query = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('wzh_scheduled_content')
            ->where('event_date_time <= :now')
            ->andWhere('evaluated_date_time is null')
            ->orderBy('event_date_time', 'ASC')
            ->setParameter('now', $now->getTimestamp())
        ;

        return $query->execute()->fetchAllAssociative();
    }

    private function createContentScheduletByContentIdQueryBuilder(int $contentId): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('*')
            ->from('wzh_scheduled_content')
            ->where(
                $query->expr()->eq(
                    'content_id',
                    ':content_id'
                )
            )->setParameter('content_id', $contentId, Types::INTEGER)
        ;

        return $query;
    }
}
