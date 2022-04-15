<?php

namespace App\Tests\Service;

use App\Service\DnsService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;

class DnsServiceTest extends WebTestCase
{
    private const FROM = 'info@millau.ovh';
    private DnsService $service;
    private string $group;

    public function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->service = $client->getContainer()->get(DnsService::class);
        $this->group = $client->getContainer()->get(ParameterBagInterface::class)->get('telegram_group');
    }

    public function testGetGroups()
    {
        $this->assertEquals([$this->group], $this->service->getGroups([new Address(self::FROM)]));
    }
}
