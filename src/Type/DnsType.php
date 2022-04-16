<?php

namespace App\Type;

use App\Service\SendGridService;
use App\Validator\DnsRecords;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DnsType extends AbstractType
{
    private SendGridService $service;

    public function __construct(SendGridService $service)
    {
        $this->service = $service;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = [];
        if (!empty($options['data']['tld']) && !empty($options['data']['group'])) {
            $records = $this->service->getDomainRecords($options['data']['tld'], $options['data']['group']);
            foreach ($records as $record) {
                $data[] = (array)$record;
            }
        }

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
                    'label' => 'Domain name',
                    'constraints' => [
                        new NotBlank()
                    ],
                    'attr' => ['readonly' => true],
                ]
            )
            ->add(
                'records',
                CollectionType::class,
                [
                    'entry_type' => DnsRecordType::class,
                    'allow_extra_fields' => true,
                    'entry_options' => ['label' => false],
                    'label' => 'Add following DNS records',
                    'data' => $data,
                    'constraints' => [new DnsRecords()],
                ]
            )
            ->add(
                'step',
                HiddenType::class,
                [
                    'data' => DnsType::class
                ]
            )
            ->add('submit', SubmitType::class, ['label' => 'Records added, check']);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
