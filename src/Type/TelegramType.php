<?php

namespace App\Type;

use App\Validator\TelegramGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TelegramType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'group',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'Add @millaubot to your Telegram group and enter /i command',
                    'help' => 'Manage group -> Members -> Add members: @millaubot',
                    'attr' => [
                        'placeholder' => '-100000000000'
                    ],
                    'constraints' => [
                        new TelegramGroup()
                    ]
                ]
            )
            ->add(
                'step',
                HiddenType::class,
                [
                    'data' => TelegramType::class
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Next']);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
