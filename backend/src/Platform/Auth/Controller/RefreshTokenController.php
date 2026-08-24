<?php

declare(strict_types=1);

namespace Zandu\Platform\Auth\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Zandu\Platform\Api\Exception\ApplicationErrorException;
use Zandu\Platform\Auth\Refresh\InvalidRefreshToken;
use Zandu\Platform\Auth\Refresh\RefreshSessionManager;
use Zandu\Platform\Auth\Security\AuthenticatedUser;
use Zandu\SharedKernel\Error\DomainError;

final readonly class RefreshTokenController
{
    /**
     * @param UserProviderInterface<AuthenticatedUser> $userProvider
     */
    public function __construct(
        private RefreshSessionManager $refreshSessions,
        private JWTTokenManagerInterface $jwtTokens,
        private UserProviderInterface $userProvider,
    ) {}

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $token = $this->tokenFromRequest($request);

        try {
            $rotated = $this->refreshSessions->rotate($token);
            $user = $this->userProvider->loadUserByIdentifier($rotated->userIdentifier());
            if ($user->organizationId() !== $rotated->organizationId()->toString()
                || $user->authorizationVersion() !== $rotated->authorizationVersion()
            ) {
                $this->refreshSessions->revoke($rotated->token());
                throw new InvalidRefreshToken('The refresh session principal is no longer current.');
            }
        } catch (InvalidRefreshToken|AuthenticationException) {
            throw $this->invalidRefreshToken();
        }

        return new JsonResponse([
            'token' => $this->jwtTokens->createFromPayload($user, [
                'sessionId' => $rotated->sessionId()->toString(),
            ]),
            'refreshToken' => $rotated->token(),
            'refreshExpiresAt' => $rotated->expiresAt()->format(DATE_ATOM),
        ]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        try {
            $this->refreshSessions->revoke($this->tokenFromRequest($request));
        } catch (InvalidRefreshToken) {
            throw $this->invalidRefreshToken();
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function tokenFromRequest(Request $request): string
    {
        $payload = json_decode($request->getContent(), true);
        $token = is_array($payload) ? ($payload['refreshToken'] ?? null) : null;

        if (!is_string($token) || '' === $token) {
            throw $this->invalidRefreshToken();
        }

        return $token;
    }

    private function invalidRefreshToken(): ApplicationErrorException
    {
        return new ApplicationErrorException(
            DomainError::create('INVALID_REFRESH_TOKEN', 'The refresh token is invalid or inactive.'),
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
