# Millau
Domain emails in Telegram. A helper which interconnects Telegram and SendGrid via the bot.

## Features
- All domains
- Any addresses
- Direct replies

## Limitations
- No html previews
- No attachments

## Requirements
- PHP 7.4+
- Composer
- private Telegram group (aka chat)
- SendGrid token
- Public domain

## Setup
- Clone the repository, rename `.env.example` to `.env` and update the values
- Set your public domain in `services.yaml`
- Try the service locally (incoming emails are not ready yet):
```
$ composer install
$ php -S localhost:8080 -t public
```
- Deploy the service under your domain.
- Set the bot's webhook:
```
$ php bin/console app:webhook-set
```
Finally, proceed to the service.

## Demo
[millau.ovh](https://millau.ovh)

## References
- [SendGrid API](https://docs.sendgrid.com/api-reference)
- [Telegram API](https://core.telegram.org/bots/api)
