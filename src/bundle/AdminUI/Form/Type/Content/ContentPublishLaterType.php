<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content;

use Ibexa\AdminUi\Form\Type\Content\VersionInfoType;
use Ibexa\AdminUi\Form\Type\DateTimePickerType;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\ContentPublishLaterData;

class ContentPublishLaterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'version_info',
                VersionInfoType::class,
                ['label' => false]
            )
            ->add(
                'publish_date_time',
                DateTimePickerType::class
            )
            ->add(
                'publish_later',
                SubmitType::class,
                [
                    'label' => /** @Desc("Publish later") */
                        'content_schedule_publish_later_form.publish_later',
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContentPublishLaterData::class,
            'translation_domain' => 'forms',
        ]);
    }
}
