<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Tab\Dashboard;

use Ibexa\Contracts\AdminUi\Tab\AbstractTab;
use Ibexa\Contracts\AdminUi\Tab\ConditionalTabInterface;
use Ibexa\Contracts\AdminUi\Tab\OrderedTabInterface;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use JMS\TranslationBundle\Annotation\Desc;
use Pagerfanta\Adapter\CallbackAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;

class EveryoneContentScheduleTab extends AbstractTab implements OrderedTabInterface, ConditionalTabInterface
{
    public const URI_FRAGMENT = 'ibexa-tab-dashboard-everyone-wzh-schedule';
    public const PAGINATION_PARAM_NAME = 'wzh-schedule-tab-page';

    public function __construct(
        Environment $twig,
        TranslatorInterface $translator,
        private readonly PermissionResolver $permissionResolver,
        private readonly ConfigResolverInterface $configResolver,
        private readonly RequestStack $requestStack,
        private readonly ContentScheduleService $contentScheduleService,
        private readonly ContentService $contentService
    ) {
        parent::__construct($twig, $translator);
    }

    public function getIdentifier(): string
    {
        return 'wzh-schedule';
    }

    public function getName(): string
    {
        return /** @Desc("Content Schedule") */
            $this->translator->trans('tab.name.wzh_everyone_content_schedule', [], 'ibexa_dashboard');
    }

    public function getOrder(): int
    {
        return 300;
    }

    public function evaluate(array $parameters): bool
    {
        return $this->permissionResolver->hasAccess('wzh_schedule', 'read') !== false;
    }

    public function renderView(array $parameters): string
    {
        $currentPage = $this->requestStack->getCurrentRequest()->query->getInt(
            self::PAGINATION_PARAM_NAME,
            1
        );
        $defaultPaginationLimit = $this->configResolver->getParameter('pagination.location_limit');

        $now = new \DateTimeImmutable();

        $adapter = new CallbackAdapter(
            fn () => $this->contentScheduleService->loadSchedulesCount(false),
            fn ($offset, $length) => $this->contentScheduleService->loadSchedules(
                false,
                $offset,
                $length
            ),
        );

        $pagination = new Pagerfanta($adapter);

        $pagination->setMaxPerPage($defaultPaginationLimit);
        $pagination->setCurrentPage(max($currentPage, 1));
        $schedulesArray = iterator_to_array($pagination);
        $schedules = $this->mapSchedulesArray($schedulesArray, $now);

        return $this->twig->render('@ibexadesign/ui/dashboard/tab/content_schedule.html.twig', [
            'pager' => $pagination,
            'pager_options' => [
                'pageParameter' => sprintf('[%s]', self::PAGINATION_PARAM_NAME),
            ],
            'data' => $schedules,
        ]);
    }

    private function mapSchedulesArray(array $schedules, \DateTimeImmutable $now): array
    {
        $result = [];

        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $result[] = [
                "schedule" => $schedule,
                "contentInfo" => $this->contentService->loadContentInfo($schedule->contentId),
                "pastDue" => $now > $schedule->eventDateTime,
            ];
        }

        return $result;
    }
}
