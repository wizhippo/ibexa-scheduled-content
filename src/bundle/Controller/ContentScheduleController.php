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
use Wizhippo\ScheduledContentBundle\AdminUI\Tab\LocationView\ScheduleTab\ScheduleTab;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;

class ContentScheduleController extends Controller
{
    public function __construct(
        private readonly TranslatableNotificationHandlerInterface $notificationHandler,
        private readonly FormFactory $formFactory,
        private readonly ContentScheduleService $ContentScheduleService
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

            $newScheduleStrut = $this->ContentScheduleService->newScheduleCreateStruct();

            $newScheduleStrut->contentId = $data->getContentInfo()->id;
            $newScheduleStrut->eventDateTime = $data->getEventDateTime();
            $newScheduleStrut->eventAction = $data->getEventAction();
            $newScheduleStrut->remark = $data->getRemark();

            $schedule = $this->ContentScheduleService->createSchedule($newScheduleStrut);

            if ($schedule) {
                $this->notificationHandler->success(
                /** @Desc("Schedule '%id%' added.") */
                    'schedule.add.success',
                    ['%id%' => $schedule->id],
                    'schedule'
                );
            } else {
                $this->notificationHandler->error(
                /** @Desc("Schedule failed to add.") */
                    'schedule.add.error',
                    [],
                    'schedule'
                );
            }

            return new RedirectResponse(
                $this->generateUrl('ibexa.content.view', [
                    'contentId' => $contentInfo->id,
                    'locationId' => $contentInfo->mainLocationId,
                    '_fragment' => ScheduleTab::URI_FRAGMENT,
                ])
            );
        }

        return $this->redirect(
            $this->generateUrl('ibexa.content.view', [
                'contentId' => $contentInfo->id,
                'locationId' => $contentInfo->mainLocationId,
                '_fragment' => ScheduleTab::URI_FRAGMENT,
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
                $schedule = $this->ContentScheduleService->loadSchedule($scheduleId);
                $this->ContentScheduleService->deleteSchedule($schedule);
            }

            $this->notificationHandler->success(
            /** @Desc("Schedule '%id%' removed.") */
                'schedule.delete.success',
                ['%id%' => $scheduleId],
                'schedule'
            );

            return new RedirectResponse(
                $this->generateUrl('ibexa.content.view', [
                    'contentId' => $contentInfo->id,
                    'locationId' => $contentInfo->mainLocationId,
                    '_fragment' => ScheduleTab::URI_FRAGMENT,
                ])
            );
        }

        return $this->redirect(
            $this->generateUrl('ibexa.content.view', [
                'contentId' => $contentInfo->id,
                'locationId' => $contentInfo->mainLocationId,
                '_fragment' => ScheduleTab::URI_FRAGMENT,
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

            $newScheduleStrut = $this->ContentScheduleService->newScheduleUpdateStruct();

            $newScheduleStrut->eventDateTime = $data->getEventDateTime();
            $newScheduleStrut->eventAction = $data->getEventAction();
            $newScheduleStrut->remark = $data->getRemark();

            $schedule = $this->ContentScheduleService->createSchedule($newScheduleStrut);

            if ($schedule) {
                $this->notificationHandler->success(
                /** @Desc("Schedule '%id%' added.") */
                    'schedule.add.success',
                    ['%id%' => $schedule->id],
                    'schedule'
                );
            } else {
                $this->notificationHandler->error(
                /** @Desc("Schedule failed to add.") */
                    'schedule.add.error',
                    [],
                    'schedule'
                );
            }

            return new RedirectResponse(
                $this->generateUrl('ibexa.content.view', [
                    'contentId' => $contentInfo->id,
                    'locationId' => $contentInfo->mainLocationId,
                    '_fragment' => ScheduleTab::URI_FRAGMENT,
                ])
            );
        }

        return $this->redirect(
            $this->generateUrl('ibexa.content.view', [
                'contentId' => $contentInfo->id,
                'locationId' => $contentInfo->mainLocationId,
                '_fragment' => ScheduleTab::URI_FRAGMENT,
            ])
        );
    }
}
