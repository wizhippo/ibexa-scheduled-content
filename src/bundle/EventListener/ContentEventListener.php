<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\EventListener;

use Ibexa\Contracts\Core\Repository\Events\Content\DeleteContentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;

class ContentEventListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContentScheduleService $contentScheduleService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DeleteContentEvent::class => 'onDeleteContent',
        ];
    }

    public function onDeleteContent(DeleteContentEvent $event): void
    {
        $schedules = $this->contentScheduleService->loadSchedulesByContentId($event->getContentInfo()->id);

        foreach ($schedules as $schedule) {
            $this->contentScheduleService->deleteSchedule($schedule);
        }
    }
}
