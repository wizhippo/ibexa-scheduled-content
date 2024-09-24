<?php

namespace Wizhippo\Tests\Integration\Schedule\Core\Repository;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;
use Wizhippo\ScheduledContentBundle\Exception\EventActionConflictException;
use Wizhippo\ScheduledContentBundle\Exception\EventOutOfOrderException;
use Wizhippo\Tests\Integration\Schedule\IbexaKernelTestCase;

class ContentScheduleServiceTest extends IbexaKernelTestCase
{
    private ?ContentScheduleService $scheduleService;

    private ?ContentService $contentService;

    private ?ContentTypeService $contentTypeService;

    private ?LocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduleService = self::getContentScheduleService();
        $this->contentService = self::getContentService();
        $this->contentTypeService = self::getContentTypeService();
        $this->locationService = self::getLocationService();
        self::setAdministratorUser();
    }

    protected function tearDown(): void
    {
        self::setAnonymousUser();
        $this->scheduleService = null;
        $this->contentService = null;
        $this->contentTypeService = null;
        $this->locationService = null;

        parent::tearDown();
    }

    public function testCreateSchedule(): Schedule
    {
        $content = $this->createHiddenContent();

        $eventDateTime = new \DateTimeImmutable('tomorrow +1000 minutes');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();

        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $eventDateTime;
        $scheduleCreateStruct->remark = 'test remark';

        $schedule = $this->scheduleService->createSchedule($scheduleCreateStruct);

        $this->assertEquals(1, $schedule->id);
        $this->assertEquals($content->id, $schedule->contentId);
        $this->assertEquals($scheduleCreateStruct->eventAction, $schedule->eventAction);
        $this->assertEquals($scheduleCreateStruct->remark, $schedule->remark);
        $this->assertEquals($scheduleCreateStruct->eventDateTime, $schedule->eventDateTime);
        $this->assertNull($schedule->evaluatedDateTime);

        return $schedule;
    }

    /**
     * @depends testCreateSchedule
     */
    public function testLoadSchedule(Schedule $schedule)
    {
        $loadedSchedule = $this->scheduleService->loadSchedule($schedule->id);

        $this->assertEquals($loadedSchedule->id, $schedule->id);
        $this->assertEquals($loadedSchedule->eventAction, $schedule->eventAction);
        $this->assertEquals($loadedSchedule->remark, $schedule->remark);
        $this->assertEquals($loadedSchedule->evaluatedDateTime, $schedule->evaluatedDateTime);
        $this->assertEquals($loadedSchedule->eventDateTime, $schedule->eventDateTime);

        return $loadedSchedule;
    }

    /**
     * @depends testLoadSchedule
     */
    public function testUpdateSchedule(Schedule $schedule)
    {
        $now = new \DateTimeImmutable('today');

        $scheduleUpdateCreateStruct = $this->scheduleService->newScheduleUpdateStruct();

        $scheduleUpdateCreateStruct->remark = 'updated remark';
        $scheduleUpdateCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleUpdateCreateStruct->eventDateTime = $now;

        $updatedSchedule = $this->scheduleService->updateSchedule($schedule, $scheduleUpdateCreateStruct);

        $this->assertEquals($updatedSchedule->id, $schedule->id);
        $this->assertEquals($updatedSchedule->eventAction, $scheduleUpdateCreateStruct->eventAction);
        $this->assertEquals($updatedSchedule->remark, $scheduleUpdateCreateStruct->remark);
        $this->assertEquals($updatedSchedule->eventDateTime, $scheduleUpdateCreateStruct->eventDateTime);

        return $updatedSchedule;
    }

    /**
     * @depends testLoadSchedule
     */
    public function testUpdateSchedulePartial(Schedule $schedule)
    {
        $scheduleUpdateCreateStruct = $this->scheduleService->newScheduleUpdateStruct();

        $scheduleUpdateCreateStruct->remark = 'updated remark';

        $updatedSchedule = $this->scheduleService->updateSchedule($schedule, $scheduleUpdateCreateStruct);

        $this->assertEquals($updatedSchedule->id, $schedule->id);
        $this->assertEquals($updatedSchedule->eventAction, $schedule->eventAction);
        $this->assertEquals($updatedSchedule->remark, $scheduleUpdateCreateStruct->remark);
        $this->assertEquals($updatedSchedule->eventDateTime, $schedule->eventDateTime);

        return $updatedSchedule;
    }

    /**
     * @depends testLoadSchedule
     */
    public function testEvaluateSchedule(Schedule $schedule)
    {
        $this->assertNull($schedule->evaluatedDateTime);

        $this->scheduleService->evaluateSchedule($schedule, false);

        $loadedSchedule = $this->scheduleService->loadSchedule($schedule->id);
        $this->assertNotNull($loadedSchedule->evaluatedDateTime);
    }

    /**
     * @depends testUpdateSchedulePartial
     */
    public function testDeleteSchedule(Schedule $schedule)
    {
        $this->scheduleService->deleteSchedule($schedule);

        $this->expectException(NotFoundException::class);
        $this->scheduleService->loadSchedule($schedule->id);
    }

    public function testNoSchedulesExistForDeletedContent()
    {
        $content = $this->createHiddenContent();

        $eventDateTime = new \DateTimeImmutable('tomorrow +1000 minutes');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();

        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $eventDateTime;
        $scheduleCreateStruct->remark = 'test remark';

        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $this->assertGreaterThan(0, $this->scheduleService->loadSchedulesByContentIdCount($content->id));

        $this->contentService->deleteContent($content->contentInfo);

        $this->assertEquals(0, $this->scheduleService->loadSchedulesByContentIdCount($content->id));
    }

    public function testLoadSchedulesByNotEvaluated()
    {
        $schedules = $this->createSchedulesData($this->createHiddenContent());

        $loadedSchedules = $this->scheduleService->loadSchedulesByNeedEvaluation($schedules[1]->eventDateTime);
        $loadedSchedulesArray = $loadedSchedules->getSchedules();

        $this->assertCount(2, $loadedSchedulesArray);
        $this->assertEquals($schedules[0]->id, $loadedSchedulesArray[0]->id);
        $this->assertEquals($schedules[1]->id, $loadedSchedulesArray[1]->id);
    }

    public function testCreateScheduleShowConflicts()
    {
        $content = $this->createHiddenContent();

        // add extra to make sure we pull last element in order
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +1000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +2000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // show -> show BAD
        $this->expectException(EventActionConflictException::class);
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +3000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // show -> hide GOOD
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +4000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // show -> trash GOOD
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_TRASH;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +5000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // trash -> show BAD
        $this->expectException(EventActionConflictException::class);
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +6000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);
    }

    public function testCreateScheduleHideConflicts()
    {
        $content = $this->createHiddenContent();

        // add extra to make sure we pull last element in order
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +1000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +2000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // hide -> hide BAD
        $this->expectException(EventActionConflictException::class);
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +3000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // hide -> show GOOD
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +4000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // hide -> trash GOOD
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_TRASH;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +5000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        // trash -> hide BAD
        $this->expectException(EventActionConflictException::class);
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +6000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);
    }

    public function testCreateScheduleOutOfOrder()
    {
        $content = $this->createHiddenContent();

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +1000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +2000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $this->expectException(EventOutOfOrderException::class);
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable('tomorrow +2000 minutes');
        $this->scheduleService->createSchedule($scheduleCreateStruct);
    }

    public function testCreateScheduleAsAnonymousUserShouldFail()
    {
        $content = $this->createHiddenContent();
        self::setAnonymousUser();

        $tomorrow = new \DateTimeImmutable('tomorrow');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();

        $scheduleCreateStruct->eventAction = 'show';
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventDateTime = $tomorrow;
        $scheduleCreateStruct->remark = 'test remark';

        $this->expectException(UnauthorizedException::class);

        return $this->scheduleService->createSchedule($scheduleCreateStruct);
    }

    public function testUpdateScheduleBadActionShouldFail()
    {
        $schedules = $this->createSchedulesData($this->createHiddenContent());

        $scheduleUpdateCreateStruct = $this->scheduleService->newScheduleUpdateStruct();
        $scheduleUpdateCreateStruct->eventAction = Schedule::ACTION_HIDE;
        $this->expectException(EventActionConflictException::class);
        $this->scheduleService->updateSchedule($schedules[1], $scheduleUpdateCreateStruct);

        $scheduleUpdateCreateStruct = $this->scheduleService->newScheduleUpdateStruct();
        $scheduleUpdateCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $this->expectException(EventActionConflictException::class);
        $this->scheduleService->updateSchedule($schedules[2], $scheduleUpdateCreateStruct);

        $scheduleUpdateCreateStruct = $this->scheduleService->newScheduleUpdateStruct();
        $scheduleUpdateCreateStruct->eventAction = Schedule::ACTION_TRASH;
        $this->expectException(EventActionConflictException::class);
        $this->scheduleService->updateSchedule($schedules[count($schedules) - 2], $scheduleUpdateCreateStruct);
    }

    public function testDeleteScheduleBadActionShouldFail()
    {
        $schedules = $this->createSchedulesData($this->createHiddenContent());

        $this->expectException(EventActionConflictException::class);
        $this->scheduleService->deleteSchedule($schedules[1]);
    }

    public function testDeleteAllSchedules()
    {
        $schedules = $this->createSchedulesData($this->createHiddenContent());

        foreach ($schedules as $schedule) {
            $this->scheduleService->deleteSchedule($schedule);
        }
    }

    public function testEvaluateSchedulesByNeedEvaluation()
    {
        /** @var Schedule[] $schedules */
        $schedules = $this->createSchedulesData($this->createHiddenContent());
        $scheduleOffset = 4;

        $contentId = $schedules[$scheduleOffset]->contentId;
        $now = $schedules[$scheduleOffset]->eventDateTime;

        $this->scheduleService->evaluateSchedulesByNeedEvaluation($now, false);

        $loadedSchedules = array_filter(
            $this->scheduleService->loadSchedulesByContentId($contentId)->getSchedules(),
            fn ($schedule) => $schedule->evaluatedDateTime !== null
        );

        $this->assertEquals(count($schedules) - $scheduleOffset, count($loadedSchedules));
    }

    private function createHiddenContent(): Content
    {
        $contentType = $this->contentTypeService->loadContentTypeByIdentifier('article');

        $locationCreateStruct = $this->locationService->newLocationCreateStruct(2);

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreate->setField('title', 'scheduled test article - '.rand());

        $content = $this->contentService->createContent(
            $contentCreate,
            [$locationCreateStruct]
        );

        $content = $this->contentService->publishVersion(
            $content->getVersionInfo()
        );

        $this->contentService->hideContent($content->contentInfo);

        return $this->contentService->loadContent($content->contentInfo->id);
    }

    private function createSchedulesData(Content $content): array
    {
        $i = 1000;
        $schedules = [];

        for (; $i <= 8000; $i += 1000) {
            $date = new \DateTimeImmutable("tomorrow +$i minutes");
            if ($i % 2000 === 0) {
                $action = Schedule::ACTION_SHOW;
            } else {
                $action = Schedule::ACTION_HIDE;
            }
            $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
            $scheduleCreateStruct->contentId = $content->id;
            $scheduleCreateStruct->eventAction = $action;
            $scheduleCreateStruct->eventDateTime = $date;
            $schedules[] = $this->scheduleService->createSchedule($scheduleCreateStruct);
        }

        $i += 1000;
        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_TRASH;
        $scheduleCreateStruct->eventDateTime = new \DateTimeImmutable("tomorrow +$i minutes");
        $schedules[] = $this->scheduleService->createSchedule($scheduleCreateStruct);

        return $schedules;
    }
}
