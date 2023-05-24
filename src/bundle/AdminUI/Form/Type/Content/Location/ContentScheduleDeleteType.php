<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Type\Content\Location;

use Ibexa\AdminUi\Form\Type\Content\ContentInfoType;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wizhippo\ScheduledContentBundle\AdminUI\Form\Data\Content\Location\ContentScheduleDeleteData;

class ContentScheduleDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'content_info',
                ContentInfoType::class,
                ['label' => false]
            )
            ->add(
                'schedules',
                CollectionType::class,
                [
                    'entry_type' => CheckboxType::class,
                    'required' => false,
                    'allow_add' => true,
                    'entry_options' => ['label' => false],
                    'label' => false,
                ]
            )
            ->add(
                'remove',
                SubmitType::class,
                [
                    'attr' => ['hidden' => true],
                    'label' => /** @Desc("Delete") */
                        'content_location_remove_form.remove',
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContentScheduleDeleteData::class,
            'translation_domain' => 'forms',
        ]);
    }
}
