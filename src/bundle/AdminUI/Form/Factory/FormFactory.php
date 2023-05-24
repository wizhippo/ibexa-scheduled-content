<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Factory;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\StringUtil;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleAddData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleDeleteData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleUpdateData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location\ContentScheduleAddType;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location\ContentScheduleDeleteType;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location\ContentScheduleUpdateType;

class FormFactory
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    public function addSchedule(
        ContentScheduleAddData $data = null,
        ?string $name = null
    ): FormInterface {
        $name = $name ?: StringUtil::fqcnToBlockPrefix(ContentScheduleAddType::class);

        return $this->formFactory->createNamed($name, ContentScheduleAddType::class, $data);
    }

    public function deleteSchedule(
        ContentScheduleDeleteData $data = null,
        ?string $name = null
    ): FormInterface {
        $name = $name ?: StringUtil::fqcnToBlockPrefix(ContentScheduleDeleteType::class);

        return $this->formFactory->createNamed($name, ContentScheduleDeleteType::class, $data);
    }

    public function updateSchedule(
        ContentScheduleUpdateData $data = null,
        ?string $name = null
    ): FormInterface {
        $name = $name ?: StringUtil::fqcnToBlockPrefix(ContentScheduleUpdateType::class);

        return $this->formFactory->createNamed($name, ContentScheduleUpdateType::class, $data);
    }
}
