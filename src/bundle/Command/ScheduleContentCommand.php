<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Command;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Core\Repository\Repository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;

class ScheduleContentCommand extends Command
{
    public static $defaultName = 'wzh:schedule-content';

    public function __construct(
        private readonly Connection $connection,
        private readonly ContentScheduleService $contentScheduleService,
        private readonly Repository $repository,
        private ?LoggerInterface $logger = null
    ) {
        parent::__construct();
        $this->logger = $this->logger ?? new NullLogger();
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->addOption('--dry-run', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');

        /**
         * We update all pending actions in order.
         * TODO: Do we want to delete instead? Do we want to do the only most recent action instead?
         */
        $this->repository->sudo(
            function (Repository $repository) use ($output, $isDryRun) {
                $now = new \DateTimeImmutable();

                /** @var Schedule $schedule */
                foreach ($this->contentScheduleService->loadSchedulesByNeedEvaluation($now) as $schedule) {
                    $contentInfo = $repository->getContentService()->loadContentInfo($schedule->contentId);
                    if ($contentInfo->status !== ContentInfo::STATUS_PUBLISHED) {
                        $msg = "Content {$contentInfo->id} for schedule {$schedule->id} is not published taking no action";
                        $this->logger->info($msg);
                        $output->isVerbose() && $output->writeln($msg);
                    } else {
                        if ($schedule->eventAction === Schedule::ACTION_HIDE && !$contentInfo->isHidden) {
                            if (!$isDryRun) {
                                $repository->getContentService()->hideContent($contentInfo);
                            }
                            $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Hide";
                            $this->logger->info($msg);
                            $output->isVerbose() && $output->writeln($msg);
                        } elseif ($schedule->eventAction === Schedule::ACTION_SHOW && $contentInfo->isHidden) {
                            if (!$isDryRun) {
                                $repository->getContentService()->revealContent($contentInfo);
                            }
                            $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Show";
                            $this->logger->info($msg);
                            $output->isVerbose() && $output->writeln($msg);
                        } elseif ($schedule->eventAction === Schedule::ACTION_TRASH) {
                            if (!$isDryRun) {
                                $repository->getTrashService()->trash($contentInfo->getMainLocation());
                            }
                            $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Trash";
                            $this->logger->info($msg);
                            $output->isVerbose() && $output->writeln($msg);
                        }
                    }
                    if (!$isDryRun) {
                        $this->contentScheduleService->evaluateSchedule($schedule);
                    }
                    $msg = "Schedule: {$schedule->id} {$schedule->eventDateTime->format(DATE_W3C)} Action: Evaluated";
                    $this->logger->info($msg);
                    $output->isVerbose() && $output->writeln($msg);
                }
            }
        );

        return self::SUCCESS;
    }

    protected function getPendingScheduleInfoWithMostRecentMarked(\DateTime $now): \Traversable
    {
        $query = $this->connection->createQueryBuilder()
            ->select('content_id, id')
            ->addSelect(
                "CASE
                WHEN ROW_NUMBER() OVER (PARTITION BY content_id ORDER BY event_date_time DESC) = 1 THEN 1
                ELSE 0
              END AS is_first_row"
            )
            ->from('wzh_scheduled_content')
            ->where('event_date_time <= :now')
            ->andWhere('evaluated is NULL')
            ->orderBy('content_id')
            ->addOrderBy('event_date_time', 'DESC')
            ->setParameter('now', $now->getTimestamp())
        ;

        return $query->execute()->iterateAssociative();
    }
}
