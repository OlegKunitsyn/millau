<?php

namespace App\Security;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class AppAuthenticator extends AbstractAuthenticator
{
    private FlashBagInterface $flash;

    public function __construct(FlashBagInterface $flash)
    {
        $this->flash = $flash;
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->request->has('login');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * In /etc/fail2ban/jail.conf under [sshd] set
     * port = 0:65535
     * and restart fail2ban
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // fail2ban
        openlog('sshd', LOG_PID, LOG_AUTH);
        syslog(LOG_ALERT, "Authentication failure for millau from {$request->getClientIp()}");

        // delay next try
        sleep(2);

        // notify user
        $this->flash->set('error', 'Authentication failed. Please check your username and password.');
        return null;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $password = $request->request->get('login')['password'];
            $user = $request->request->get('login')['user'];
            return new Passport(new UserBadge($user), new PasswordCredentials($password));
        } catch (Exception $ignored) {
        }
        throw new AuthenticationException();
    }
}
