<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class PublicAuthRateLimitSubscriber
{
    public function __construct(
        private RateLimiterFactory $authenticationLimiter,
        private RateLimiterFactory $onboardingLimiter,
        private RateLimiterFactory $tokenSessionLimiter,
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 2048)]
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $limiter = $this->limiterFor($request);
        if (null === $limiter) {
            return;
        }

        $limit = $limiter->create($this->key($request))->consume();
        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());
        $event->setResponse(new JsonResponse([
            'code' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'Too many authentication requests. Please retry later.',
        ], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => (string) $retryAfter]));
    }

    private function limiterFor(Request $request): ?RateLimiterFactory
    {
        if ('POST' !== $request->getMethod()) {
            return null;
        }

        return match (true) {
            '/api/auth/login' === $request->getPathInfo() => $this->authenticationLimiter,
            '/api/auth/register' === $request->getPathInfo(),
            1 === preg_match('#^/api/auth/invitations/[^/]+/register$#', $request->getPathInfo()) => $this->onboardingLimiter,
            in_array($request->getPathInfo(), ['/api/auth/refresh', '/api/auth/logout'], true) => $this->tokenSessionLimiter,
            default => null,
        };
    }

    private function key(Request $request): string
    {
        $identity = '';
        if (in_array($request->getPathInfo(), ['/api/auth/login', '/api/auth/register'], true)) {
            $payload = json_decode($request->getContent(), true);
            $email = is_array($payload) ? ($payload['email'] ?? '') : '';
            $identity = is_string($email) ? strtolower(trim($email)) : '';
        }

        return hash('sha256', implode('|', [
            $request->getClientIp() ?? 'unknown',
            $request->getPathInfo(),
            $identity,
        ]));
    }
}
