<?php

namespace App\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\NotBlank;

class DomainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'group',
                TextType::class,
                [
                    'label' => 'Telegram group',
                    'constraints' => [
                        new NotBlank()
                    ],
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'tld',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'Enter your domain name',
                    'attr' => [
                        'placeholder' => 'example.com'
                    ],
                    'constraints' => [
                        new Hostname()
                    ]
                ]
            )
            ->add(
                'step',
                HiddenType::class,
                [
                    'data' => DomainType::class
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Next']);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
