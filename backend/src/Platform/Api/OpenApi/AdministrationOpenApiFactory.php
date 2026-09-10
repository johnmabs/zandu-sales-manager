<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;

final readonly class AdministrationOpenApiFactory implements OpenApiFactoryInterface
{
    private const array ERRORS = [
        400 => ['VALIDATION_ERROR', 'The request payload is invalid.'],
        401 => ['UNAUTHENTICATED', 'Authentication is required.'],
        403 => ['FORBIDDEN', 'The authenticated actor is not authorized.'],
        404 => ['NOT_FOUND', 'The resource was not found, including cross-tenant resources.'],
        409 => ['CONFLICT', 'The request conflicts with the current resource state.'],
        422 => ['DOMAIN_RULE_VIOLATION', 'The operation violates a domain rule.'],
    ];

    public function __construct(private OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            if (!$this->isAdministrationPath($path)) {
                continue;
            }

            $openApi->getPaths()->addPath($path, $this->documentErrors($pathItem));
        }

        $this->documentAuthentication($openApi);

        return $openApi;
    }

    private function documentAuthentication(OpenApi $openApi): void
    {
        $credentials = [
            'type' => 'object',
            'required' => ['email', 'password'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'format' => 'password'],
            ],
        ];
        $tokens = [
            'type' => 'object',
            'required' => ['token', 'refreshExpiresAt'],
            'properties' => [
                'token' => ['type' => 'string'],
                'refreshExpiresAt' => ['type' => 'string', 'format' => 'date-time'],
            ],
        ];

        $openApi->getPaths()->addPath('/api/auth/login', new PathItem(post: new Operation(
            operationId: 'auth_login',
            tags: ['Authentication'],
            responses: [
                '200' => new Response('Authentication tokens.', $this->jsonContent($tokens)),
                '401' => new Response('Invalid credentials.'),
            ],
            summary: 'Authenticates a user.',
            requestBody: new RequestBody('Credentials.', $this->jsonContent($credentials), true),
            security: [],
        )));
        $openApi->getPaths()->addPath('/api/auth/refresh', new PathItem(post: new Operation(
            operationId: 'auth_refresh',
            tags: ['Authentication'],
            responses: [
                '200' => new Response('Rotated authentication tokens.', $this->jsonContent($tokens)),
                '401' => new Response('Invalid refresh token.'),
            ],
            summary: 'Rotates a refresh token.',
            security: [],
        )));
        $openApi->getPaths()->addPath('/api/auth/logout', new PathItem(post: new Operation(
            operationId: 'auth_logout',
            tags: ['Authentication'],
            responses: ['204' => new Response('Refresh session revoked.')],
            summary: 'Revokes a refresh session.',
            security: [],
        )));
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return \ArrayObject<string, MediaType>
     */
    private function jsonContent(array $schema): \ArrayObject
    {
        return new \ArrayObject(['application/json' => new MediaType(new \ArrayObject($schema))]);
    }

    private function isAdministrationPath(string $path): bool
    {
        foreach (['/api/session', '/api/organizations', '/api/stores', '/api/member-invitations', '/api/invitations', '/api/members', '/api/roles', '/api/products', '/api/categories', '/api/product-prices', '/api/price-lists', '/api/catalog', '/api/stock', '/api/cash-registers', '/api/cash-sessions', '/api/cash-movements'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function documentErrors(PathItem $pathItem): PathItem
    {
        return $pathItem
            ->withGet($this->withErrors($pathItem->getGet()))
            ->withPost($this->withErrors($pathItem->getPost()))
            ->withPatch($this->withErrors($pathItem->getPatch()))
            ->withDelete($this->withErrors($pathItem->getDelete()));
    }

    private function withErrors(?Operation $operation): ?Operation
    {
        if (null === $operation) {
            return null;
        }

        foreach (self::ERRORS as $status => [$code, $description]) {
            $schema = new \ArrayObject([
                'type' => 'object',
                'required' => ['code', 'message', 'correlationId'],
                'properties' => [
                    'code' => ['type' => 'string', 'enum' => [$code]],
                    'message' => ['type' => 'string'],
                    'correlationId' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
                ],
            ]);
            $content = new \ArrayObject([
                'application/json' => new MediaType($schema, [
                    'code' => $code,
                    'message' => $description,
                    'correlationId' => '0198e463-147c-72d5-b75a-a936797ff9c8',
                ]),
            ]);
            $operation = $operation->withResponse($status, new Response($description, $content));
        }

        return $operation;
    }
}
