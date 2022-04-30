<?php

namespace App\Tests\Service;

use App\Service\SendGridService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;

class SendGridServiceTest extends WebTestCase
{
    private const TLD = 'millau.ovh';
    private string $group;
    private SendGridService $service;

    public function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->service = $client->getContainer()->get(SendGridService::class);
        $this->group = $client->getContainer()->get(ParameterBagInterface::class)->get('telegram_group');
    }

    public function testIsDomainValid()
    {
        $this->assertTrue($this->service->isDomainValid(self::TLD, $this->group));
    }

    public function testGetDomainRecords()
    {
        $records = $this->service->getDomainRecords(self::TLD, $this->group);
        $this->assertCount(5, $records);
    }

    public function testSetInbound()
    {
        $this->assertTrue($this->service->setInbox(self::TLD, $this->group));
    }

    public function testGetRegisteredDomain()
    {
        $domain = $this->service->getRegisteredDomain(self::TLD, $this->group);
        $this->assertEquals(self::TLD, $domain->domain);

        $domain = $this->service->getRegisteredDomain('example.com', $this->group);
        $this->assertNull($domain);
    }

    public function testParseInboundHtml()
    {
        $email = $this->service->parseInbound(json_decode(file_get_contents(__DIR__ . '/html.json'), true));
        /** @var Address $from */
        $from = $email->getFrom()[0];
        $this->assertEquals('Firstname Lastname', $from->getName());
        $this->assertEquals('firstname.lastname@gmail.com', $from->getAddress());
        $this->assertEquals('Re: HTML email', $email->getSubject());
        $this->assertTrue(strpos($email->getHtmlBody(), '<b>thank you for your Purchase Request.</b>') !== false);
    }

    public function testParseInboundText()
    {
        $email = $this->service::parseInbound(json_decode(file_get_contents(__DIR__ . '/text.json'), true));

        /** @var Address[] $from */
        $from = $email->getFrom();
        $this->assertEquals('Firstname Lastname', $from[0]->getName());
        $this->assertEquals('firstname.lastname@gmail.com', $from[0]->getAddress());

        /** @var Address[] $to */
        $to = $email->getTo();
        $this->assertEquals('First Name', $to[0]->getName());
        $this->assertEquals('first@example.com', $to[0]->getAddress());
        $this->assertEquals('Last Name', $to[1]->getName());
        $this->assertEquals('last@example.com', $to[1]->getAddress());

        $this->assertEquals('Text email', $email->getSubject());
        $this->assertEquals("Text body\n", $email->getTextBody());
        $this->assertEquals("<div dir=\"ltr\">Text body<br></div>\n", $email->getHtmlBody());
    }

    public function testParseInboundAttachment()
    {
        $email = $this->service::parseInbound(json_decode(file_get_contents(__DIR__ . '/attachment.json'), true));

        /** @var Address[] $from */
        $from = $email->getFrom();
        $this->assertEquals('First Last', $from[0]->getName());
        $this->assertEquals('first.last@gmail.com', $from[0]->getAddress());

        /** @var Address[] $to */
        $to = $email->getTo();
        $this->assertEquals('', $to[0]->getName());
        $this->assertEquals('info@millau.ovh', $to[0]->getAddress());

        $this->assertEquals('Test attachment', $email->getSubject());
        $this->assertEquals("Test\r\nbody\n", $email->getTextBody());
        $this->assertEquals("<div dir=\"ltr\"><div>Test</div><div>body</div><div><div dir=\"ltr\" class=\"gmail_signature\" data-smartmail=\"gmail_signature\"><div dir=\"ltr\"><br></div></div></div></div>\n", $email->getHtmlBody());

        $this->assertCount(1, $email->getAttachments());
        $this->assertEquals('image', $email->getAttachments()[0]->getMediaType());
        $this->assertEquals('jpeg', $email->getAttachments()[0]->getMediaSubtype());
        $this->assertEquals(11331, strlen($email->getAttachments()[0]->getBody()));
    }
}
