<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Tab\LocationView\ScheduleTab;

use Ibexa\Contracts\AdminUi\Tab\AbstractEventDispatchingTab;
use Ibexa\Contracts\AdminUi\Tab\ConditionalTabInterface;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use JMS\TranslationBundle\Annotation\Desc;
use Pagerfanta\Adapter\CallbackAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleAddData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleDeleteData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Factory\FormFactory;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;

final class ScheduleTab extends AbstractEventDispatchingTab implements ConditionalTabInterface
{
    public const URI_FRAGMENT = 'ibexa-tab-location-view-wzh-schedule';
    private const PAGINATION_PARAM_NAME = 'wzh-schedule-tab-page';

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        private readonly PermissionResolver $permissionResolver,
        private readonly ConfigResolverInterface $configResolver,
        private readonly RequestStack $requestStack,
        private readonly FormFactory $formFactory,
        private readonly ContentScheduleService $ContentScheduleService
    ) {
        parent::__construct($twig, $translator, $eventDispatcher);
    }

    public function getIdentifier(): string
    {
        return 'wzh-schedule';
    }

    public function getName(): string
    {
        return /** @Desc("Schedule") */
            $this->translator->trans('tab.name.wzh-schedule', [], 'locationview');
    }

    public function evaluate(array $parameters): bool
    {
        return $this->permissionResolver->hasAccess('wzh_schedule', 'read') !== false;
    }

    public function getTemplate(): string
    {
        return '@ibexadesign/content/tab/wzh-schedule/tab.html.twig';
    }

    public function getTemplateParameters(array $contextParameters = []): array
    {
        /** @var Content $content */
        $content = $contextParameters['content'];
        /** @var Location $location */
        $location = $contextParameters['location'];
        $versionInfo = $content->getVersionInfo();
        $contentInfo = $versionInfo->getContentInfo();
        $schedules = [];
        $pagination = null;
        $defaultPaginationLimit = $this->configResolver->getParameter('pagination.location_limit');

        if ($contentInfo->published) {
            $currentPage = $this->requestStack->getCurrentRequest()->query->getInt(
                self::PAGINATION_PARAM_NAME,
                1
            );

            $adapter = new CallbackAdapter(
                fn () => $this->ContentScheduleService->loadSchedulesByContentIdCount($content->id),
                fn ($offset, $length) => $this->ContentScheduleService->loadSchedulesByContentId(
                    $content->id,
                    $offset,
                    $length
                ),
            );

            $pagination = new Pagerfanta($adapter);

            $pagination->setMaxPerPage($defaultPaginationLimit);
            $pagination->setCurrentPage(max($currentPage, 1));
            $schedulesArray = iterator_to_array($pagination);
            $schedules = array_map(fn ($item) => $item, $schedulesArray);
        }

        $scheduleAddForm = $this->createScheduleAddForm($contentInfo);
        $scheduleDeleteForm = $this->createScheduleDeleteForm($contentInfo, $this->getScheduleChoices($schedules));

        $canEditSchedule = $this->permissionResolver->canUser(
            'wzh_schedule',
            'add',
            $content
        );
        // We grant access to choose a valid Location from UDW. Now it is not possible to filter locations
        // and show only those which user has access to
        $canCreate = false !== $this->permissionResolver->hasAccess('content', 'create');
        $canEdit = $this->permissionResolver->canUser(
            'content',
            'edit',
            $location->getContentInfo()
        );

        $viewParameters = [
            'pager' => $pagination,
            'pager_options' => [
                'pageParameter' => sprintf('[%s]', self::PAGINATION_PARAM_NAME),
            ],
            'schedules' => $schedules,
            'form_content_schedule_add' => $scheduleAddForm->createView(),
            'form_content_schedule_delete' => $scheduleDeleteForm->createView(),
            'can_add' => $canEditSchedule && ($canCreate || $canEdit),
        ];

        return array_replace($contextParameters, $viewParameters);
    }

    private function createScheduleAddForm(ContentInfo $contentInfo): FormInterface
    {
        return $this->formFactory->addSchedule(
            new ContentScheduleAddData($contentInfo)
        );
    }

    private function createScheduleDeleteForm(ContentInfo $contentInfo, array $schedules): FormInterface
    {
        return $this->formFactory->deleteSchedule(
            new ContentScheduleDeleteData($contentInfo, $schedules)
        );
    }

    private function getScheduleChoices(array $schedules): array
    {
        $scheduleIds = array_column($schedules, 'id');

        return array_combine($scheduleIds, array_fill_keys($scheduleIds, false));
    }
}
