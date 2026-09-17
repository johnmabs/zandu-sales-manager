<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Platform\Api\EventSubscriber\CursorPaginationResponseSubscriber;
use Zandu\Platform\Api\State\PaginatedCollectionProvider;

final class CursorPaginationResponseSubscriberTest extends TestCase
{
    public function testItExposesTheNextCursorAsAResponseHeader(): void
    {
        $request = new Request();
        $request->attributes->set(PaginatedCollectionProvider::NEXT_CURSOR_ATTRIBUTE, 'opaque-cursor');
        $response = new Response();

        (new CursorPaginationResponseSubscriber())->onResponse(new ResponseEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        self::assertSame('opaque-cursor', $response->headers->get('X-Next-Cursor'));
    }
}
