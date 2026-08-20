<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zandu\Platform\Api\EventSubscriber\IdempotencyKeyRequestSubscriber;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;

final class IdempotencyKeyRequestSubscriberTest extends TestCase
{
    public function testItMakesAProvidedKeyAvailableAsATypedValue(): void
    {
        $request = new Request(server: ['HTTP_IDEMPOTENCY_KEY' => 'checkout-42']);

        (new IdempotencyKeyRequestSubscriber())->onKernelRequest($this->event($request));

        $key = $request->attributes->get(IdempotencyKeyRequestSubscriber::REQUEST_ATTRIBUTE);
        self::assertInstanceOf(IdempotencyKey::class, $key);
        self::assertSame('checkout-42', $key->toString());
    }

    public function testItAllowsARequestWithoutAKey(): void
    {
        $request = new Request();

        (new IdempotencyKeyRequestSubscriber())->onKernelRequest($this->event($request));

        self::assertFalse($request->attributes->has(IdempotencyKeyRequestSubscriber::REQUEST_ATTRIBUTE));
    }

    public function testItRejectsAnInvalidProvidedKeyWithAStableError(): void
    {
        $request = new Request(server: ['HTTP_IDEMPOTENCY_KEY' => '']);

        try {
            (new IdempotencyKeyRequestSubscriber())->onKernelRequest($this->event($request));
            self::fail('An invalid Idempotency-Key should be rejected.');
        } catch (ApplicationErrorException $exception) {
            self::assertSame('INVALID_IDEMPOTENCY_KEY', $exception->error()->code());
        }
    }

    private function event(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
