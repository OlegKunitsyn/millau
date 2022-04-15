<?php

namespace App\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DnsRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'type',
                TextType::class,
                [
                    'label' => 'Type',
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'host',
                TextType::class,
                [
                    'label' => 'Name',
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'data',
                TextType::class,
                [
                    'label' => 'Value',
                    'attr' => [
                        'readonly' => true,
                    ],
                ]
            )
            ->add(
                'copy',
                ButtonType::class,
                [
                    'label' => 'Copy to clipboard',
                    'attr' => ['onclick' => 'copyToClipboard(document.getElementById(this.id.replace("copy", "data")))']
                ]
            );
    }
}
