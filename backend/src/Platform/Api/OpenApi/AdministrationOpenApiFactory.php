<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
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
        $openApi = $this->withoutInvalidLoginPath(($this->decorated)($context));

        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            if (!$this->isAdministrationPath($path)) {
                continue;
            }

            $openApi->getPaths()->addPath($path, $this->documentErrors($pathItem));
        }

        $this->documentAuthentication($openApi);
        $this->documentOnboarding($openApi);
        $this->requireExpectedVersionOnPatches($openApi);

        return $openApi;
    }

    private function requireExpectedVersionOnPatches(OpenApi $openApi): void
    {
        $schemas = $openApi->getComponents()->getSchemas();
        foreach ($openApi->getPaths()->getPaths() as $pathItem) {
            $requestBody = $pathItem->getPatch()?->getRequestBody();
            if (null === $requestBody) {
                continue;
            }

            foreach ($requestBody->getContent() as $mediaType) {
                $schema = $mediaType->getSchema();
                $reference = $schema['$ref'] ?? null;
                if (!is_string($reference)) {
                    continue;
                }

                $name = basename($reference);
                $component = $schemas[$name] ?? null;
                if (!$component instanceof \ArrayObject || !isset($component['properties']['expectedVersion'])) {
                    continue;
                }

                $required = $component['required'] ?? [];
                $component['required'] = array_values(array_unique([...$required, 'expectedVersion']));
            }
        }
    }

    private function withoutInvalidLoginPath(OpenApi $openApi): OpenApi
    {
        $paths = new Paths();
        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            if ('api_auth_login' !== $path) {
                $paths->addPath($path, $pathItem);
            }
        }

        return $openApi->withPaths($paths);
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

    private function documentOnboarding(OpenApi $openApi): void
    {
        $registration = [
            'type' => 'object',
            'required' => ['email', 'password', 'organizationName', 'countryCode', 'defaultCurrency', 'defaultTimeZone', 'defaultLocale'],
            'properties' => [
                'email' => ['type' => 'string', 'format' => 'email'],
                'password' => ['type' => 'string', 'format' => 'password'],
                'organizationName' => ['type' => 'string'],
                'countryCode' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 2],
                'defaultCurrency' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 3],
                'defaultTimeZone' => ['type' => 'string'],
                'defaultLocale' => ['type' => 'string'],
            ],
        ];
        $invitationRegistration = [
            'type' => 'object',
            'required' => ['password'],
            'properties' => ['password' => ['type' => 'string', 'format' => 'password']],
        ];
        $result = [
            'type' => 'object',
            'required' => ['userId', 'organizationId'],
            'properties' => [
                'userId' => ['type' => 'string', 'format' => 'uuid'],
                'organizationId' => ['type' => 'string', 'format' => 'uuid'],
            ],
        ];

        $openApi->getPaths()->addPath('/api/auth/register', new PathItem(post: new Operation(
            operationId: 'auth_register',
            tags: ['Onboarding'],
            responses: [
                '201' => new Response('User and first organization created.', $this->jsonContent($result)),
                '409' => new Response('An account already exists.', $this->publicErrorContent('ACCOUNT_ALREADY_EXISTS')),
                '422' => new Response('The registration payload is invalid.', $this->publicErrorContent('INVALID_REGISTRATION')),
            ],
            summary: 'Creates the first organization owner.',
            description: 'Atomically creates a global user, an active organization, its active membership and the ORGANIZATION_OWNER assignment.',
            requestBody: new RequestBody('Owner and organization registration data.', $this->jsonContent($registration), true),
            security: [],
        )));
        $openApi->getPaths()->addPath('/api/auth/invitations/{token}/register', new PathItem(post: new Operation(
            operationId: 'auth_invitation_register',
            tags: ['Onboarding'],
            responses: [
                '201' => new Response('Invited user registered.', $this->jsonContent($result)),
                '409' => new Response('The invitation registration conflicts with existing state.', $this->publicErrorContent('INVITATION_REGISTRATION_CONFLICT')),
                '422' => new Response('The invitation is invalid or inactive.', $this->publicErrorContent('INVALID_INVITATION_REGISTRATION')),
            ],
            summary: 'Registers a user from an invitation.',
            description: 'Creates the invited user and membership, assigns the intended roles and consumes the single-use invitation token atomically.',
            parameters: [new Parameter('token', 'path', 'Opaque invitation token.', true, schema: ['type' => 'string'])],
            requestBody: new RequestBody('Password for the invited account.', $this->jsonContent($invitationRegistration), true),
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

    /** @return \ArrayObject<string, MediaType> */
    private function publicErrorContent(string $code): \ArrayObject
    {
        return $this->jsonContent([
            'type' => 'object',
            'required' => ['code', 'message'],
            'properties' => [
                'code' => ['type' => 'string', 'enum' => [$code]],
                'message' => ['type' => 'string'],
            ],
        ]);
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
