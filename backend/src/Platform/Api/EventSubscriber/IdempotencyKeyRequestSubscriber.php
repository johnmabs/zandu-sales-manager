<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\EventSubscriber;

use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\SharedKernel\Error\DomainError;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;

final class IdempotencyKeyRequestSubscriber implements EventSubscriberInterface
{
    public const HEADER_NAME = 'Idempotency-Key';
    public const REQUEST_ATTRIBUTE = '_zandu_idempotency_key';

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 90]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $value = $event->getRequest()->headers->get(self::HEADER_NAME);

        if (null === $value) {
            return;
        }

        try {
            $key = IdempotencyKey::fromString($value);
        } catch (InvalidArgumentException) {
            throw new ApplicationErrorException(DomainError::create(
                'INVALID_IDEMPOTENCY_KEY',
                'The Idempotency-Key header is invalid.',
            ));
        }

        $event->getRequest()->attributes->set(self::REQUEST_ATTRIBUTE, $key);
    }
}
