<?php

namespace App\Service;

use App\Entity\Domain;
use App\Entity\Record;
use InvalidArgumentException;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\TypeException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use ZBateson\MailMimeParser\MailMimeParser;

class SendGridService
{
    public const FROM = 'From:';
    public const TO = 'To:';

    private const INBOUND_MX = 'mx.sendgrid.net';
    private SendGrid $client;
    private string $inbox;
    private DnsService $dns;

    public function __construct(string $apiKey, string $domain, UrlGeneratorInterface $router, DnsService $dns)
    {
        $this->dns = $dns;
        $this->client = new SendGrid($apiKey);
        $host = $router->getContext()->getHost();
        $scheme = $router->getContext()->getScheme();
        $router->getContext()
            ->setHost($domain)
            ->setScheme('https');
        $this->inbox = $router->generate('route_inbox', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $router->getContext()
            ->setHost($host)
            ->setScheme($scheme);
    }

    public static function parseInbound(array $message): Email
    {
        $email = new Email();
        $email
            ->addFrom(Address::create($message['from']))
            ->subject($message['subject']);
        foreach (Address::createArray(explode(',', $message['to'])) as $address) {
            $email->addTo($address);
        }

        if (!empty($message['email'])) {
            // raw mode
            $parser = new MailMimeParser();
            $message = $parser->parse($message['email'], false);
            $email
                ->html($message->getHtmlContent())
                ->text($message->getTextContent());
            for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
                $attachment = $message->getAttachmentPart($i);
                $email->attach($attachment->getContent(), $attachment->getFilename(), $attachment->getContentType());
            }
        } else {
            // parsed mode
            $email
                ->html($message['html'] ?? $message['text'])
                ->text($message['text'] ?? strip_tags($message['html'], '<pre><a><b><i><u><br><code><s><span>'));
        }

        return $email;
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function getRegisteredDomains(): array
    {
        $response = $this->client->client->whitelabel()->domains()->get();
        return json_decode($response->body(), true);
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function getRegisteredDomain(string $tld, string $group): ?Domain
    {
        foreach ($this->getRegisteredDomains() as $item) {
            if ($item['domain'] === $tld) {
                $domain = new Domain();
                $domain->domain = $item['domain'];
                $domain->id = $item['id'];
                $domain->subdomain = $item['subdomain'];

                $record = new Record();
                $record->type = 'mx';
                $record->host = $tld;
                $record->data = self::INBOUND_MX . '.';
                $domain->records[] = $record;
                $record = new Record();
                $record->type = 'txt';
                $record->host = $tld;
                $record->data = $this->dns->getGroupData($group, $tld);
                $domain->records[] = $record;

                foreach ($item['dns'] as $value) {
                    $record = new Record();
                    $record->type = $value['type'];
                    $record->host = $value['host'];
                    $record->data = $value['data'];
                    $domain->records[] = $record;
                }
                return $domain;
            }
        }
        return null;
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function isDomainValid(string $tld, string $group): bool
    {
        if ($domain = $this->getRegisteredDomain($tld, $group)) {
            $response = $this->client->client->whitelabel()->domains()->_($domain->id)->validate()->post();
            $json = json_decode($response->body(), true);
            $mxHosts = [];
            getmxrr($tld, $mxHosts);
            return $json['valid'] && in_array(self::INBOUND_MX, $mxHosts) && null !== $this->dns->getGroup($tld);
        }
        return false;
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function deleteDomain(string $tld): void
    {
        foreach ($this->getRegisteredDomains() as $item) {
            if ($item['domain'] === $tld) {
                $this->client->client->whitelabel()->domains()->_($item['id'])->delete();
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setInbox(string $tld, string $group): bool
    {
        if ($domain = $this->getRegisteredDomain($tld, $group)) {
            $hostname = $domain->domain;
            $check = $this->client->client->user()->webhooks()->parse()->settings()->_($hostname)->get();
            $json = json_decode($check->body(), true);
            if (isset($json['errors'])) {
                $create = $this->client->client->user()->webhooks()->parse()->settings()->post([
                    'url' => $this->inbox,
                    'hostname' => $hostname,
                    'spam_check' => false,
                    'send_raw' => true
                ]);
                $json = json_decode($create->body(), true);
                if (isset($json['errors'])) {
                    throw new InvalidArgumentException(implode(' ', $json['errors'][0]));
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @return Record[]
     * @throws InvalidArgumentException
     * @noinspection PhpUndefinedMethodInspection
     */
    public function getDomainRecords(string $tld, string $group): array
    {
        if ($domain = $this->getRegisteredDomain($tld, $group)) {
            return $domain->records;
        }

        $response = $this->client->client->whitelabel()->domains()->post([
            'domain' => $tld,
            'subdomain' => '',
            'username' => '',
            'ips' => [],
            'custom_spf' => false,
            'default' => false,
            'automatic_security' => true
        ]);
        $json = json_decode($response->body(), true);
        if (isset($json['errors'])) {
            throw new InvalidArgumentException(implode(' ', $json['errors'][0]));
        }

        $records = [];
        $record = new Record();
        $record->type = 'mx';
        $record->host = $tld;
        $record->data = self::INBOUND_MX . '.';
        $records[] = $record;

        $record = new Record();
        $record->type = 'txt';
        $record->host = $tld;
        $record->data = $this->dns->getGroupData($group, $tld);
        $records[] = $record;
        foreach ($json['dns'] as $item) {
            $record = new Record();
            $record->type = $item['type'];
            $record->host = $item['host'];
            $record->data = $item['data'];
            $records[] = $record;
        }
        return $records;
    }

    /**
     * @throws TypeException
     * @throws InvalidArgumentException
     */
    public function sendEmail(Email $email): void
    {
        $mail = new Mail();
        $mail->setSubject($email->getSubject());
        $mail->addContent('text/plain', $email->getTextBody());
        $mail->addContent('text/html', $email->getHtmlBody());
        foreach ($email->getTo() as $address) {
            $mail->addTo($address->getAddress(), $address->getName());
        }
        foreach ($email->getFrom() as $address) {
            $mail->setFrom($address->getAddress(), $address->getName());
        }
        $response = $this->client->send($mail);
        if ($response->statusCode() >= 300) {
            throw new InvalidArgumentException($response->body());
        }
    }

    /**
     * @throws TypeException
     * @throws InvalidArgumentException
     */
    public function sendText(string $from, string $to, string $subject, string $message): string
    {
        $mail = new Mail();
        $mail->setFrom($from);
        $mail->addTo($to);
        $mail->setSubject($subject);
        $mail->addContent('text/plain', $message);
        $response = $this->client->send($mail);
        if ($response->statusCode() >= 300) {
            throw new InvalidArgumentException($response->body());
        }
        return $to;
    }
}
