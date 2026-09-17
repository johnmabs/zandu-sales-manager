<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zandu\Platform\Api\State\PaginatedCollectionProvider;

final class CursorPaginationResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $nextCursor = $event->getRequest()->attributes->get(PaginatedCollectionProvider::NEXT_CURSOR_ATTRIBUTE);

        if (is_string($nextCursor)) {
            $event->getResponse()->headers->set('X-Next-Cursor', $nextCursor);
        }
    }
}
