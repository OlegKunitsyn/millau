<?php

namespace App\Tests\Service;

use App\Service\SendGridService;
use App\Service\TelegramService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Address;

class TelegramServiceTest extends WebTestCase
{
    private TelegramService $service;
    private string $group;

    public function setUp(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $this->service = $client->getContainer()->get(TelegramService::class);
        $this->group = $client->getContainer()->get(ParameterBagInterface::class)->get('telegram_group');
    }

    public function testIsOnline()
    {
        $this->assertTrue($this->service->isOnline());
    }

    public function testCreatePostText()
    {
        $email = SendGridService::parseInbound(json_decode(file_get_contents(__DIR__ . '/text.json'), true));
        $id = $this->service->createPostEmail($email, $this->group);
        $this->assertNotNull($id);
    }

    public function testCreatePostHtml()
    {
        $email = SendGridService::parseInbound(json_decode(file_get_contents(__DIR__ . '/html.json'), true));
        $id = $this->service->createPostEmail($email, $this->group);
        $this->assertNotNull($id);
    }

    public function testCreatePostAttachment()
    {
        $email = SendGridService::parseInbound(json_decode(file_get_contents(__DIR__ . '/attachment.json'), true));
        $id = $this->service->createPostEmail($email, $this->group);
        $this->assertNotNull($id);
    }

    public function testCreatePostWelcome()
    {
        $email = SendGridService::parseInbound(json_decode(file_get_contents(__DIR__ . '/welcome.json'), true));
        $id = $this->service->createPostEmail($email, $this->group);
        $this->assertNotNull($id);
    }

    public function testCreatePostSib()
    {
        $email = SendGridService::parseInbound(json_decode(file_get_contents(__DIR__ . '/sib.json'), true));
        $id = $this->service->createPostEmail($email, $this->group);
        $this->assertNotNull($id);
    }

    public function testParseReply()
    {
        $email = $this->service->parseReply(json_decode(file_get_contents(__DIR__ . '/reply.json'), true));

        /** @var Address[] $from */
        $from = $email->getFrom();
        $this->assertEquals('', $from[0]->getName());
        $this->assertEquals('info@millau.ovh', $from[0]->getAddress());

        /** @var Address[] $to */
        $to = $email->getTo();
        $this->assertEquals('Firstname Lastname', $to[0]->getName());
        $this->assertEquals('firstname.lastname@gmail.com', $to[0]->getAddress());

        $this->assertEquals('Re: Test subject', $email->getSubject());
        $this->assertEquals("Hello!\nThis is my reply.\n:)", $email->getTextBody());
        $this->assertEquals("Hello!<br />\nThis is my reply.<br />\n:)", $email->getHtmlBody());
    }

    public function testParseCommandE()
    {
        $email = $this->service->parseCommandE(json_decode(file_get_contents(__DIR__ . '/command.json'), true));

        /** @var Address[] $from */
        $from = $email->getFrom();
        $this->assertEquals('', $from[0]->getName());
        $this->assertEquals('firstname.lastname@gmail.com', $from[0]->getAddress());

        /** @var Address[] $to */
        $to = $email->getTo();
        $this->assertEquals('', $to[0]->getName());
        $this->assertEquals('info@millau.ovh', $to[0]->getAddress());

        $this->assertEquals('Subject goes here', $email->getSubject());
        $this->assertEquals("click reply", $email->getTextBody());
        $this->assertEquals("click reply", $email->getHtmlBody());
    }

    public function testCreatePostEmail()
    {
        $email = $this->service->parseCommandE(json_decode(file_get_contents(__DIR__ . '/command.json'), true));
        $id = $this->service->createPostEmail($email, $this->group);
        $this->assertNotNull($id);
    }
}
