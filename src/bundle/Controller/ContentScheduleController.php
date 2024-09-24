<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Contracts\AdminUi\Notification\TranslatableNotificationHandlerInterface;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleAddData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleDeleteData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleUpdateData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Factory\FormFactory;
use Wizhippo\ScheduledContentBundle\AdminUI\Tab\LocationView\ContentScheduleTab;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;

class ContentScheduleController extends Controller
{
    public function __construct(
        private readonly TranslatableNotificationHandlerInterface $notificationHandler,
        private readonly FormFactory $formFactory,
        private readonly ContentScheduleService $contentScheduleService
    ) {
    }

    public function add(Request $request): Response
    {
        $form = $this->formFactory->addSchedule(
            new ContentScheduleAddData()
        );
        $form->handleRequest($request);

        $contentInfo = $form->getData()->getContentInfo();

        if ($form->isSubmitted()) {
            /** @var ContentScheduleAddData $data */
            $data = $form->getData();

            $newScheduleCreateStruct = $this->contentScheduleService->newScheduleCreateStruct();

            $newScheduleCreateStruct->contentId = $data->getContentInfo()->getId();
            $newScheduleCreateStruct->eventDateTime = \DateTimeImmutable::createFromMutable($data->getEventDateTime());
            $newScheduleCreateStruct->eventAction = $data->getEventAction();
            $newScheduleCreateStruct->remark = $data->getRemark();

            try {
                $schedule = $this->contentScheduleService->createSchedule($newScheduleCreateStruct);
                $this->notificationHandler->success(
                /** @Desc("Schedule '%id%' added.") */
                    'schedule.add.success',
                    ['%id%' => $schedule->id],
                    'schedule'
                );
            } catch (\InvalidArgumentException $e) {
                $this->notificationHandler->error(
                /** @Ignore */
                    $e->getMessage(),
                    [],
                    'schedule'
                );
            }

            return new RedirectResponse(
                $this->generateUrl('ibexa.content.view', [
                    'contentId' => $contentInfo->id,
                    'locationId' => $contentInfo->mainLocationId,
                    '_fragment' => ContentScheduleTab::URI_FRAGMENT,
                ])
            );
        }

        return $this->redirect(
            $this->generateUrl('ibexa.content.view', [
                'contentId' => $contentInfo->id,
                'locationId' => $contentInfo->mainLocationId,
                '_fragment' => ContentScheduleTab::URI_FRAGMENT,
            ])
        );
    }

    public function delete(Request $request): Response
    {
        $form = $this->formFactory->deleteSchedule(
            new ContentScheduleDeleteData()
        );
        $form->handleRequest($request);

        $contentInfo = $form->getData()->getContentInfo();

        if ($form->isSubmitted()) {
            /** @var ContentScheduleDeleteData $data */
            $data = $form->getData();
            $contentInfo = $data->getContentInfo();
            foreach ($data->getSchedules() as $scheduleId => $selected) {
                $schedule = $this->contentScheduleService->loadSchedule($scheduleId);
                try {
                    $this->contentScheduleService->deleteSchedule($schedule);
                    $this->notificationHandler->success(
                    /** @Desc("Schedule '%id%' removed.") */
                        'schedule.delete.success',
                        ['%id%' => $schedule->id],
                        'schedule'
                    );
                } catch (\InvalidArgumentException $e) {
                    $this->notificationHandler->error(
                    /** @Ignore */ $e->getMessage(),
                        [],
                        'schedule'
                    );
                }
            }

            return new RedirectResponse(
                $this->generateUrl('ibexa.content.view', [
                    'contentId' => $contentInfo->id,
                    'locationId' => $contentInfo->mainLocationId,
                    '_fragment' => ContentScheduleTab::URI_FRAGMENT,
                ])
            );
        }

        return $this->redirect(
            $this->generateUrl('ibexa.content.view', [
                'contentId' => $contentInfo->id,
                'locationId' => $contentInfo->mainLocationId,
                '_fragment' => ContentScheduleTab::URI_FRAGMENT,
            ])
        );
    }

    public function update(Request $request): Response
    {
        $form = $this->formFactory->updateSchedule(
            new ContentScheduleUpdateData()
        );
        $form->handleRequest($request);

        $contentInfo = $form->getData()->getContentInfo();

        if ($form->isSubmitted()) {
            /** @var ContentScheduleUpdateData $data */
            $data = $form->getData();

            $schedule = $this->contentScheduleService->loadSchedule($data->getId());

            $scheduleUpdateStruct = $this->contentScheduleService->newScheduleUpdateStruct();

            $scheduleUpdateStruct->eventDateTime = $data->getEventDateTime();
            $scheduleUpdateStruct->eventAction = $data->getEventAction();
            $scheduleUpdateStruct->remark = $data->getRemark();

            try {
                $schedule = $this->contentScheduleService->updateSchedule($schedule, $scheduleUpdateStruct);
                $this->notificationHandler->success(
                /** @Desc("Schedule '%id%' added.") */
                    'schedule.add.success',
                    ['%id%' => $schedule->id],
                    'schedule'
                );
            } catch (\InvalidArgumentException $e) {
                $this->notificationHandler->error(
                /** @Ignore */
                    $e->getMessage(),
                    [],
                    'schedule'
                );
            }

            return new RedirectResponse(
                $this->generateUrl('ibexa.content.view', [
                    'contentId' => $contentInfo->id,
                    'locationId' => $contentInfo->mainLocationId,
                    '_fragment' => ContentScheduleTab::URI_FRAGMENT,
                ])
            );
        }

        return $this->redirect(
            $this->generateUrl('ibexa.content.view', [
                'contentId' => $contentInfo->id,
                'locationId' => $contentInfo->mainLocationId,
                '_fragment' => ContentScheduleTab::URI_FRAGMENT,
            ])
        );
    }
}
