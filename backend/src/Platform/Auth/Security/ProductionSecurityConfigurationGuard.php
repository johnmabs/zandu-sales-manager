<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Security;

use LogicException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ProductionSecurityConfigurationGuard
{
    public function __construct(
        private string $environment,
        private string $appSecret,
        private string $jwtPassphrase,
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 4096)]
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || 'prod' !== $this->environment) {
            return;
        }

        if (strlen($this->appSecret) < 32) {
            throw new LogicException('APP_SECRET must contain at least 32 characters in production.');
        }
        if (strlen($this->jwtPassphrase) < 32) {
            throw new LogicException('JWT_PASSPHRASE must contain at least 32 characters in production.');
        }
    }
}
