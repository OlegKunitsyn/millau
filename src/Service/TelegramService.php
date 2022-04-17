<?php

namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TelegramService
{
    private const COMMAND_E = '/e';
    private const COMMAND_I = '/i';
    private string $token;
    private string $outbox;
    private string $devGroup;

    public function __construct(string $token, string $group, string $domain, UrlGeneratorInterface $router)
    {
        $this->token = $token;
        $this->devGroup = $group;
        $host = $router->getContext()->getHost();
        $scheme = $router->getContext()->getScheme();
        $router->getContext()
            ->setHost($domain)
            ->setScheme('https');
        $this->outbox = $router->generate('route_outbox', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $router->getContext()
            ->setHost($host)
            ->setScheme($scheme);
    }

    public function setWebhook(): bool
    {
        $response = file_get_contents('https://api.telegram.org/bot' . $this->token . '/setWebhook?url=' . urlencode($this->outbox));
        $json = json_decode($response, true);
        return $json['ok'] ?? false;
    }

    public function getWebhook(): ?string
    {
        $response = file_get_contents('https://api.telegram.org/bot' . $this->token . '/getWebhookInfo');
        $json = json_decode($response, true);
        return $json['result']['url'] ?? null;
    }

    public function log(string $message): bool
    {
        $data = [
            'chat_id' => $this->devGroup,
            'text' => $message
        ];
        $response = file_get_contents('https://api.telegram.org/bot' . $this->token . '/sendMessage?' . http_build_query($data));
        $json = json_decode($response, true);
        return $json['ok'] ?? false;
    }

    public function parseReply(array $message): ?Email
    {
        if (empty($message['message']['reply_to_message']['message_id'])) {
            return null;
        }
        $lines = explode("\n", $message['message']['reply_to_message']['text'] ?? '');
        if (count($lines) <= 3 || strpos($lines[0], SendGridService::FROM) !== 0 || strpos($lines[1], SendGridService::TO) !== 0) {
            return null;
        }

        $subject = 0 === strpos($lines[2], 'Re: ') ? $lines[2] : 'Re: ' . $lines[2];
        $email = new Email();
        $email
            ->subject($subject)
            ->text($message['message']['text'] ?? '')
            ->html(nl2br($message['message']['text'] ?? ''));
        $addresses = explode(',', trim(str_replace(SendGridService::FROM, '', $lines[0])));
        foreach ($addresses as $address) {
            $parts = explode(' ', $address);
            $address = array_pop($parts);
            $email->addTo(new Address($address, implode(' ', $parts)));
        }
        $addresses = explode(',', trim(str_replace(SendGridService::TO, '', $lines[1])));
        foreach ($addresses as $address) {
            $parts = explode(' ', $address);
            $address = array_pop($parts);
            $email->addFrom(new Address($address, implode(' ', $parts)));
        }

        return $email;
    }

    /**
     * Get group id
     */
    public function parseCommandI(array $message): ?string
    {
        if (strpos($message['message']['text'], self::COMMAND_I) !== 0 || !isset($message['message']['entities'])) {
            return null;
        }
        return $message['message']['chat']['id'] ?? null;
    }

    /**
     * Send email in format FROM TO SUBJECT
     */
    public function parseCommandE(array $message): ?Email
    {
        if (strpos($message['message']['text'], self::COMMAND_E) !== 0 || !isset($message['message']['entities'])) {
            return null;
        }
        $addresses = [];
        $subjectOffset = 0;
        foreach ($message['message']['entities'] as $entity) {
            if ('email' === $entity['type']) {
                $addresses[] = substr($message['message']['text'], $entity['offset'], $entity['length']);
            }
            $subjectOffset = max($subjectOffset, $entity['offset'] + $entity['length'] + 1);
        }
        if (0 === count($addresses)) {
            return null;
        }

        $subject = trim(substr($message['message']['text'], $subjectOffset));

        $email = new Email();
        $email
            ->subject($subject)
            ->text('click reply')
            ->html('click reply')
            ->addTo(Address::create(array_shift($addresses)));
        foreach ($addresses as $address) {
            $email->addFrom(Address::create($address));
        }

        return $email;
    }

    /**
     * API https://core.telegram.org/bots/api#sendmessage
     */
    public function createPostId(string $group = null): ?string
    {
        if (null === $group) {
            return null;
        }

        return $this->createPost($group, [
            "<b>Group/Chat ID</b> $group",
        ]);
    }

    public function createPostEmail(Email $email, string $group = null): ?string
    {
        if (null === $group) {
            return null;
        }

        /** @var Address $address */
        $from = [];
        foreach ($email->getFrom() as $address) {
            $from[] = "{$address->getName()} {$address->getAddress()}";
        }
        /** @var Address $address */
        $to = [];
        foreach ($email->getTo() as $address) {
            $to[] = "{$address->getName()} {$address->getAddress()}";
        }

        return $this->createPost($group, [
            '<b>' . SendGridService::FROM . '</b> ' . implode(', ', $from),
            '<b>' . SendGridService::TO . '</b> ' . implode(', ', $to),
            "<b>{$email->getSubject()}</b>",
            strip_tags($email->getTextBody(), '<pre><a><b><i><u><code><s>'),
        ]);
    }

    public function isMember(string $group): bool
    {
        $data = [
            'chat_id' => $group
        ];
        $response = @file_get_contents('https://api.telegram.org/bot' . $this->token . '/getChat?' . http_build_query($data));
        $json = json_decode($response, true);
        return $json['ok'] ?? false;
    }

    public function isOnline(): bool
    {
        $response = file_get_contents('https://api.telegram.org/bot' . $this->token . '/getMe');
        $json = json_decode($response, true);
        return $json['ok'] ?? false;
    }

    /**
     * API https://core.telegram.org/bots/api#sendmessage
     */
    private function createPost(string $group, array $lines): ?string
    {
        $data = [
            'chat_id' => $group,  // group begins with @
            'text' => implode("\n", $lines),
            'disable_notification' => true,
            'disable_web_page_preview' => false,
            'parse_mode' => 'HTML'
        ];
        $response = file_get_contents('https://api.telegram.org/bot' . $this->token . '/sendMessage?' . http_build_query($data));
        $json = json_decode($response, true);
        if ($json['ok'] ?? false) {
            return $json['result']['message_id'];
        }
        return null;
    }
}
