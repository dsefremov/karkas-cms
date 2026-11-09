<?php

declare(strict_types=1);

namespace Karkas\Application\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class AdminAuthService
{
    private const SESSION_AUTHENTICATED = 'karkas_admin_authenticated';
    private const SESSION_REAUTH_AT = 'karkas_admin_reauth_at';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly string $adminPassword,
    ) {
    }

    public function login(string $password): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        $valid = hash_equals($this->adminPassword, $password);
        if ($valid) {
            $this->session()->set(self::SESSION_AUTHENTICATED, true);
            $this->markReauthenticated();
        }

        return $valid;
    }

    public function logout(): void
    {
        $this->session()->remove(self::SESSION_AUTHENTICATED);
        $this->session()->remove(self::SESSION_REAUTH_AT);
    }

    public function isAuthenticated(): bool
    {
        return (bool) $this->session()->get(self::SESSION_AUTHENTICATED, false);
    }

    public function assertAuthenticated(): void
    {
        if (!$this->isAuthenticated()) {
            throw new AdminAuthException('Admin authentication required.');
        }
    }

    public function reauthenticate(string $password): void
    {
        if (!$this->login($password)) {
            throw new AdminAuthException('Re-authentication failed.');
        }
    }

    public function assertRecentReauthentication(): void
    {
        $this->assertAuthenticated();
        $reauthAt = $this->session()->get(self::SESSION_REAUTH_AT);
        if (!is_int($reauthAt) || time() - $reauthAt > 300) {
            throw new AdminAuthException('Recent re-authentication required (within 5 minutes).');
        }
    }

    public function markReauthenticated(): void
    {
        $this->session()->set(self::SESSION_REAUTH_AT, time());
    }

    public function resolveReauthFromRequest(Request $request): void
    {
        $password = $request->headers->get('X-Karkas-Reauth');
        if (null === $password || '' === $password) {
            throw new AdminAuthException('X-Karkas-Reauth header required for this operation.');
        }
        $this->reauthenticate($password);
    }

    public function isConfigured(): bool
    {
        return '' !== $this->adminPassword;
    }

    private function session(): SessionInterface
    {
        $session = $this->requestStack->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }
}
