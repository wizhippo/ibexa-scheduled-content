<?php

declare(strict_types=1);

namespace Wizhippo\ScheduledContentBundle\AdminUI\Form\Extension;

use Ibexa\ContentForms\Form\Type\Content\ContentEditType;
use JMS\TranslationBundle\Annotation\Desc;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ContentEditTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [ContentEditType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publish_hidden', SubmitType::class, [
                'label' => /** @Desc("Publish hidden") */ 'publish.hidden',
                'attr' => [
                    'hidden' => true,
                ],
            ])
        ;
    }
}
