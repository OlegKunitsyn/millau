<?php

namespace App\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class TestEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'group',
                HiddenType::class,
                [
                    'constraints' => [new NotBlank()],
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'tld',
                HiddenType::class,
                [
                    'constraints' => [new NotBlank()],
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'to',
                EmailType::class,
                [
                    'label' => 'To',
                    'data' => 'info@' . $options['data']['tld'],
                    'constraints' => [
                        new Email(),
                        new NotBlank()
                    ],
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'subject',
                TextType::class,
                [
                    'label' => 'Subject',
                    'data' => 'Test subject',
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'message',
                TextareaType::class,
                [
                    'label' => 'Message',
                    'data' => 'Test message',
                    'constraints' => [
                        new NotBlank()
                    ]
                ]
            )
            ->add(
                'step',
                HiddenType::class,
                [
                    'data' => TestEmailType::class
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Send test email']);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
