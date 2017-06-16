<?php

namespace AppBundle\Form;

use AppBundle\Entity\Website;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebsiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('domain', TextType::class, [
                'label' => 'Domain name',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'width:100% !important'
                ],
                'label_attr' => [
                    'class' => 'col-md-3'
                ]
            ])
            ->add('reference', TextType::class, [
                'label' => 'Reference (3 letters)',
                'attr' => [
                    'class' => 'form-control',
                    'style' => 'width:100% !important'
                ],
                'label_attr' => [
                    'class' => 'col-md-3'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Website::class
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_website';
    }
}
