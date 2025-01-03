<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Processor;

use Ibexa\ContentForms\Data\NewnessCheckable;
use Ibexa\ContentForms\Event\FormActionEvent;
use Ibexa\ContentForms\Form\Processor\ContentFormProcessor;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class PublishLaterContentFormProcessor implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContentFormProcessor $innerContentFormProcessor,
        private readonly LocationService $locationService,
        private readonly RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'content.edit.publishLater' => ['onPublishLater', 20],
        ];
    }

    public function onPublishLater(FormActionEvent $event): void
    {
        $this->innerContentFormProcessor->processSaveDraft($event);

        /** @var Content $content */
        $draft = $event->getPayload('content');
        /** @var \Ibexa\ContentForms\Data\Content\ContentCreateData|\Ibexa\ContentForms\Data\Content\ContentUpdateData $data */
        $data = $event->getData();
        $form = $event->getForm();
        $formConfig = $form->getConfig();
        $languageCode = $formConfig->getOption('languageCode');
        $referrerLocation = $event->getOption('referrerLocation');
        $contentLocation = $this->resolveLocation($draft, $referrerLocation, $data);

        $defaultUrl = $this->router->generate('wzh_ibexa_admin_location_schedules.publishLater', [
            'contentId' => $draft->id,
            'versionNo' => $draft->getVersionInfo()->getVersionNo(),
            'languageCode' => $languageCode,
            'locationId' => null !== $contentLocation ? $contentLocation->getId() : null,
        ]);
        $event->setResponse(new RedirectResponse($formConfig->getAction() ?: $defaultUrl));
    }

    private function resolveLocation(Content $content, ?Location $referrerLocation, NewnessCheckable $data): ?Location
    {
        if ($data->isNew() || (!$content->contentInfo->published && null === $content->contentInfo->mainLocationId)) {
            return null; // no location exists until new content is published
        }

        return $referrerLocation ?? $this->locationService->loadLocation($content->contentInfo->mainLocationId);
    }
}
