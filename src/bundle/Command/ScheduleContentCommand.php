<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\Command;

use Ibexa\Core\Repository\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;

class ScheduleContentCommand extends Command
{
    public static $defaultName = 'wzh:schedule-content';

    public function __construct(
        private readonly ContentScheduleService $contentScheduleService,
        private readonly Repository $repository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption('--dry-run', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');

        $this->repository->sudo(
            function (Repository $repository) use ($output, $isDryRun) {
                $now = new \DateTimeImmutable();
                $this->contentScheduleService->evaluateSchedulesByNeedEvaluation($now, $isDryRun);
            }
        );

        return self::SUCCESS;
    }
}
