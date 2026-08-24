<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Zandu\Platform\Api\EventSubscriber\CorrelationIdRequestSubscriber;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $correlationId = $request->attributes->get(CorrelationIdRequestSubscriber::REQUEST_ATTRIBUTE);

        return new JsonResponse([
            'code' => 'UNAUTHENTICATED',
            'message' => 'Authentication is required.',
            'correlationId' => $correlationId instanceof CorrelationId ? $correlationId->toString() : null,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
