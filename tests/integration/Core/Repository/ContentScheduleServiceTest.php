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
use Wizhippo\Tests\Integration\Schedule\IbexaKernelTestCase;

class ContentScheduleServiceTest extends IbexaKernelTestCase
{
    private ContentScheduleService $scheduleService;

    private ContentService $contentService;

    private ContentTypeService $contentTypeService;

    private LocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheduleService = self::getContentScheduleService();
        $this->contentService = self::getContentService();
        $this->contentTypeService = self::getContentTypeService();
        $this->locationService = self::getLocationService();
        self::setAdministratorUser();
    }

    public function testCreateHiddenContent(): Content
    {
        return $this->createHiddenContent();
    }

    /**
     * @depends testCreateHiddenContent
     */
    public function testCreateSchedule(Content $content)
    {
        $tomorrow = new \DateTime('tomorrow');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();

        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $tomorrow;
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
        $now = new \DateTime('today');

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

        $this->scheduleService->evaluateSchedule($schedule);

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
        $schedule = $this->scheduleService->loadSchedule($schedule->id);
    }

    public function testNoSchedulesExistForDeletedContent()
    {
        $content = $this->createHiddenContent();

        $tomorrow = new \DateTime('tomorrow');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();

        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $tomorrow;
        $scheduleCreateStruct->remark = 'test remark';

        $schedule = $this->scheduleService->createSchedule($scheduleCreateStruct);

        $this->assertGreaterThan(0, $this->scheduleService->loadSchedulesByContentIdCount($content->id));

        $this->contentService->deleteContent($content->contentInfo);

        $this->assertEquals(0, $this->scheduleService->loadSchedulesByContentIdCount($content->id));
    }


    public function testLoadSchedulesByNotEvaluated()
    {
        $content = $this->createHiddenContent();

        $dateLessThen = new \DateTime('@1000');
        $dateEquals = new \DateTime('@2000');
        $dateGreaterThen = new \DateTime('@3000');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $dateLessThen;;
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $dateEquals;
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventAction = Schedule::ACTION_SHOW;
        $scheduleCreateStruct->eventDateTime = $dateGreaterThen;
        $this->scheduleService->createSchedule($scheduleCreateStruct);

        $loadedSchedules = $this->scheduleService->loadSchedulesByNotEvaluated($dateEquals);
        $loadedSchedulesArray = $loadedSchedules->getSchedules();

        $this->assertCount(2, $loadedSchedulesArray);
        $this->assertEquals($dateLessThen, $loadedSchedulesArray[0]->eventDateTime);
        $this->assertEquals($dateEquals, $loadedSchedulesArray[1]->eventDateTime);
    }

    public function testCreateScheduleAsAnonymousUserShouldFail()
    {
        $content = $this->createHiddenContent();
        self::setAnonymousUser();

        $tomorrow = new \DateTime('tomorrow');

        $scheduleCreateStruct = $this->scheduleService->newScheduleCreateStruct();

        $scheduleCreateStruct->eventAction = 'show';
        $scheduleCreateStruct->contentId = $content->id;
        $scheduleCreateStruct->eventDateTime = $tomorrow;
        $scheduleCreateStruct->remark = 'test remark';

        $this->expectException(UnauthorizedException::class);

        return $this->scheduleService->createSchedule($scheduleCreateStruct);
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
}
