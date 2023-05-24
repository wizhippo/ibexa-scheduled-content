<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location;

use Ibexa\AdminUi\Form\Type\DateTimePickerType;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleAddData;
use Wizhippo\ScheduledContentBundle\API\Repository\Values\ContentSchedule\Schedule;

class ContentScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'event_date_time',
                DateTimePickerType::class
            )
            ->add(
                'event_action',
                ChoiceType::class, [
                    'choices' => [
                        /** @Desc("Show") */ 'Show' => Schedule::ACTION_SHOW,
                        /** @Desc("Hide") */ 'Hide' => Schedule::ACTION_HIDE,
                        /** @Desc("Trash") */ 'Trash' => Schedule::ACTION_TRASH,
                    ],
                ]
            )
            ->add(
                'remark',
                TextType::class, [
                    'required' => false,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContentScheduleAddData::class,
            'translation_domain' => 'forms',
        ]);
    }
}
