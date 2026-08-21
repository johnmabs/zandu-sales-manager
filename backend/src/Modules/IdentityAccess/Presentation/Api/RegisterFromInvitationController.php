<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Zandu\Modules\IdentityAccess\Application\RegisterFromInvitation\RegisterFromInvitation;
use Zandu\Modules\IdentityAccess\Application\RegisterFromInvitation\RegisterFromInvitationHandler;
use Zandu\SharedKernel\Messaging\CorrelationId;

final readonly class RegisterFromInvitationController
{
    public function __construct(private RegisterFromInvitationHandler $handler) {}

    #[Route('/api/auth/invitations/{token}/register', name: 'api_auth_invitation_register', methods: ['POST'])]
    public function __invoke(string $token, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $password = is_array($payload) ? ($payload['password'] ?? null) : null;
        $correlationId = $request->attributes->get('_zandu_correlation_id');
        if (!is_string($password) || !$correlationId instanceof CorrelationId) {
            return new JsonResponse(['code' => 'INVALID_INVITATION_REGISTRATION', 'message' => 'A password is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = ($this->handler)(new RegisterFromInvitation($token, $password, $correlationId));
        } catch (InvalidArgumentException) {
            return $this->invalidInvitation();
        } catch (LogicException $exception) {
            return new JsonResponse(['code' => 'INVITATION_REGISTRATION_CONFLICT', 'message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'userId' => $result->userId->toString(),
            'organizationId' => $result->organizationId->toString(),
        ], Response::HTTP_CREATED);
    }

    private function invalidInvitation(): JsonResponse
    {
        return new JsonResponse([
            'code' => 'INVALID_INVITATION_REGISTRATION',
            'message' => 'The invitation is invalid or inactive.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
