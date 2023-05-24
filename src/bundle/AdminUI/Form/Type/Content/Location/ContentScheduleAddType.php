<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location;

use Ibexa\AdminUi\Form\Type\Content\ContentInfoType;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleAddData;

class ContentScheduleAddType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'content_info',
                ContentInfoType::class,
                ['label' => false]
            )
            ->add(
                'add',
                SubmitType::class,
                [
                    'label' => /** @Desc("Add schedule") */
                        'content_schedule_add_type.add',
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContentScheduleAddData::class,
            'translation_domain' => 'forms',
        ]);
    }

    public function getParent()
    {
        return ContentScheduleType::class;
    }
}
