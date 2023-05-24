<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Wizhippo\ScheduledContentBundle\ParamConverter;

use Ibexa\Bundle\Core\Converter\RepositoryParamConverter;
use Wizhippo\ScheduledContentBundle\API\Repository\ContentScheduleService;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;

class ScheduleParamConverter extends RepositoryParamConverter
{
    public function __construct(
        private readonly ContentScheduleService $contentScheduleService
    ) {
    }

    protected function getSupportedClass(): string
    {
        return Schedule::class;
    }

    protected function getPropertyName(): string
    {
        return 'scheduleId';
    }

    protected function loadValueObject($id): Schedule
    {
        return $this->contentScheduleService->loadSchedule($id);
    }
}
