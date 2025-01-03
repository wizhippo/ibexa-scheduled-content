<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Factory;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\StringUtil;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\ContentHideLaterData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\ContentPublishLaterData;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\ContentHideLaterType;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\ContentPublishLaterType;

class FormFactory
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    public function publishLater(
        ?ContentPublishLaterData $data = null,
        ?string $name = null,
        array $options = []
    ): FormInterface {
        $name = $name ?: StringUtil::fqcnToBlockPrefix(ContentPublishLaterType::class);
        $data = $data ?? new ContentPublishLaterData();

        return $this->formFactory->createNamed($name, ContentPublishLaterType::class, $data, $options);
    }

    public function hideLater(
        ?ContentHideLaterData $data = null,
        ?string $name = null,
        array $options = []
    ): FormInterface {
        $name = $name ?: StringUtil::fqcnToBlockPrefix(ContentHideLaterType::class);
        $data = $data ?? new ContentHideLaterData();

        return $this->formFactory->createNamed($name, ContentHideLaterData::class, $data, $options);
    }
}
