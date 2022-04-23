<?php

namespace App\Twig;

use App\Service\DnsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension
{
    private DnsService $service;

    public function __construct(DnsService $service)
    {
        $this->service = $service;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('group', [$this, 'group']),
        ];
    }

    public function group(string $tld): ?string
    {
        return $this->service->getGroup($tld);
    }
}
