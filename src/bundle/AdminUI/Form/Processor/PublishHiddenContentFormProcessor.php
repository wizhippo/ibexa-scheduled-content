<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Processor;

use Ibexa\ContentForms\Event\FormActionEvent;
use Ibexa\ContentForms\Form\Processor\ContentFormProcessor;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Wizhippo\ScheduledContentBundle\AdminUI\Tab\LocationView\ContentScheduleTab;

class PublishHiddenContentFormProcessor implements EventSubscriberInterface
{
    public function __construct(
        private readonly ContentFormProcessor $innerContentFormProcessor,
        private readonly ContentService $contentService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'content.edit.publish_hidden' => ['onPublishHidden', 20],
        ];
    }

    public function onPublishHidden(FormActionEvent $event): void
    {
        $this->innerContentFormProcessor->processPublish($event);

        /** @var Content $content */
        $content = $event->getPayload('content');

        $this->contentService->hideContent($content->contentInfo);

        $response = $event->getResponse();
        if ($response instanceof RedirectResponse) {
            $response->setTargetUrl($response->getTargetUrl().'#'.ContentScheduleTab::URI_FRAGMENT.'#tab');
        }
    }
}
