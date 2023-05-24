<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location;

use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleAddData;

class ContentScheduleUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'id',
                IntegerType::class,
                ['label' => false]
            )
            ->add(
                'update',
                SubmitType::class,
                [
                    'label' => /** @Desc("Update schedule") */
                        'content_schedule_update_type.update',
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

    public function getParent()
    {
        return ContentScheduleType::class;
    }
}
