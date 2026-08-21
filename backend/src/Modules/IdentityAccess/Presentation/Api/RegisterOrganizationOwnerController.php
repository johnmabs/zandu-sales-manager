<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner\RegisterOrganizationOwner;
use Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner\RegisterOrganizationOwnerHandler;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class RegisterOrganizationOwnerController
{
    public function __construct(private RegisterOrganizationOwnerHandler $handler) {}

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $correlationId = $request->attributes->get('_zandu_correlation_id');
        if (!is_array($payload) || !$correlationId instanceof CorrelationId) {
            return $this->error('INVALID_REGISTRATION', 'A valid JSON registration payload is required.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = ($this->handler)(new RegisterOrganizationOwner(
                $this->required($payload, 'email'),
                $this->required($payload, 'password'),
                $this->required($payload, 'organizationName'),
                $this->required($payload, 'countryCode'),
                $this->required($payload, 'defaultCurrency'),
                $this->required($payload, 'defaultTimeZone'),
                $this->required($payload, 'defaultLocale'),
                $correlationId,
            ));
        } catch (InvalidArgumentException $exception) {
            return $this->error('INVALID_REGISTRATION', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (LogicException $exception) {
            return $this->error('ACCOUNT_ALREADY_EXISTS', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'userId' => $result->userId->toString(),
            'organizationId' => $result->organizationId->toString(),
        ], Response::HTTP_CREATED);
    }

    /** @param array<mixed> $payload */
    private function required(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException(sprintf('Field "%s" is required.', $field));
        }

        return $value;
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return new JsonResponse(['code' => $code, 'message' => $message], $status);
    }
}
