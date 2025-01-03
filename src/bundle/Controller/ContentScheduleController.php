<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Controller;

use Ibexa\Contracts\AdminUi\Controller\Controller;
use Ibexa\Contracts\AdminUi\Notification\TranslatableNotificationHandlerInterface;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\ContentHideLaterData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\ContentPublishLaterData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Factory\FormFactory;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;

class ContentScheduleController extends Controller
{
    public function __construct(
        private readonly TranslatableNotificationHandlerInterface $notificationHandler,
        private readonly FormFactory $formFactory,
        private readonly ContentScheduleService $contentScheduleService,
        private readonly ContentService $contentService,
        private readonly LocationService $locationService
    ) {
    }

    public function publishLater(
        Request $request,
        Content $content,
        ?string $languageCode
    ): Response {
        $form = $this->formFactory->publishLater(
            new ContentPublishLaterData($content->versionInfo)
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var ContentPublishLaterData $data */
            $data = $form->getData();

            try {
                $newScheduleCreateStruct = $this->contentScheduleService->newScheduleCreateStruct();

                $newScheduleCreateStruct->contentId = $data->getVersionInfo()->getContentInfo()->getId();
                $newScheduleCreateStruct->versionNo = $data->getVersionInfo()->getVersionNo();

                $newScheduleCreateStruct->eventDateTime = \DateTimeImmutable::createFromMutable(
                    $data->getPublishDateTime()
                );
                $newScheduleCreateStruct->eventAction = Schedule::ACTION_PUBLISH;

                $schedule = $this->contentScheduleService->createSchedule($newScheduleCreateStruct);
                $this->notificationHandler->success(
                /** @Desc("Schedule '%id%' added.") */
                    'schedule.add.success',
                    ['%id%' => $schedule->id],
                    'schedule'
                );

                $referrerLocation = $request->get('referrerLocation');

                if ($referrerLocation === null) {
                    $versionInfo = $content->getVersionInfo();
                    $contentInfo = $versionInfo->getContentInfo();

                    $currentVersion = $this->contentService->loadContentByContentInfo($contentInfo);

                    if ($currentVersion->getVersionInfo()->status === VersionInfo::STATUS_PUBLISHED) {
                        $publishedContentInfo = $currentVersion->getVersionInfo()->getContentInfo();
                        $redirectionLocationId = $publishedContentInfo->mainLocationId;
                        $redirectionContentId = $publishedContentInfo->getId();
                    } else {
                        $parentLocation = $this->locationService->loadParentLocationsForDraftContent($versionInfo)[0];
                        $redirectionLocationId = $parentLocation->id;
                        $redirectionContentId = $parentLocation->contentId;
                    }
                } else {
                    $redirectionLocationId = $referrerLocation->id;
                    $redirectionContentId = $referrerLocation->contentId;
                }

                return new RedirectResponse(
                    $this->generateUrl(
                        'ibexa.content.view',
                        [
                            'contentId' => $redirectionContentId,
                            'locationId' => $redirectionLocationId,
                        ]
                    )
                );
            } catch (\InvalidArgumentException $e) {
                $this->notificationHandler->error(
                /** @Ignore */
                    $e->getMessage(),
                    [],
                    'schedule'
                );
            }
        }

        return $this->render('@ibexadesign/content/publish_later.html.twig', [
            'form_publish_later' => $form->createView(),
            'content' => $content,
            'languageCode' => $languageCode,
        ]);
    }

    public function hideLater(
        Request $request,
        Content $content,
        ?string $languageCode
    ): Response {
        $form = $this->formFactory->hideLater(
            new ContentHideLaterData($content->versionInfo)
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            /** @var ContentHideLaterData $data */
            $data = $form->getData();

            try {
                $newScheduleCreateStruct = $this->contentScheduleService->newScheduleCreateStruct();

                $newScheduleCreateStruct->contentId = $data->getVersionInfo()->getContentInfo()->getId();
                $newScheduleCreateStruct->versionNo = $data->getVersionInfo()->getVersionNo();

                $newScheduleCreateStruct->eventDateTime = \DateTimeImmutable::createFromMutable(
                    $data->getHideDateTime()
                );
                $newScheduleCreateStruct->eventAction = Schedule::ACTION_HIDE;

                $schedule = $this->contentScheduleService->createSchedule($newScheduleCreateStruct);
                $this->notificationHandler->success(
                /** @Desc("Schedule '%id%' added.") */
                    'schedule.add.success',
                    ['%id%' => $schedule->id],
                    'schedule'
                );

                $referrerLocation = $request->get('referrerLocation');

                if ($referrerLocation === null) {
                    $versionInfo = $content->getVersionInfo();
                    $contentInfo = $versionInfo->getContentInfo();

                    $currentVersion = $this->contentService->loadContentByContentInfo($contentInfo);

                    if ($currentVersion->getVersionInfo()->status === VersionInfo::STATUS_PUBLISHED) {
                        $publishedContentInfo = $currentVersion->getVersionInfo()->getContentInfo();
                        $redirectionLocationId = $publishedContentInfo->mainLocationId;
                        $redirectionContentId = $publishedContentInfo->getId();
                    } else {
                        $parentLocation = $this->locationService->loadParentLocationsForDraftContent($versionInfo)[0];
                        $redirectionLocationId = $parentLocation->id;
                        $redirectionContentId = $parentLocation->contentId;
                    }
                } else {
                    $redirectionLocationId = $referrerLocation->id;
                    $redirectionContentId = $referrerLocation->contentId;
                }

                return new RedirectResponse(
                    $this->generateUrl(
                        'ibexa.content.view',
                        [
                            'contentId' => $redirectionContentId,
                            'locationId' => $redirectionLocationId,
                        ]
                    )
                );
            } catch (\InvalidArgumentException $e) {
                $this->notificationHandler->error(
                /** @Ignore */
                    $e->getMessage(),
                    [],
                    'schedule'
                );
            }
        }

        return $this->render('@ibexadesign/content/hide_later.html.twig', [
            'form_hide_later' => $form->createView(),
            'content' => $content,
            'languageCode' => $languageCode,
        ]);
    }
}
