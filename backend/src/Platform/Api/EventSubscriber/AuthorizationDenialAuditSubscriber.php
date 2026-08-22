<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class AuthorizationDenialAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TenantTransaction $transaction,
        private SecurityAuditTrail $audit,
        private Clock $clock,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 20]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $denial = $event->getThrowable();
        if (!$denial instanceof AuthorizationDenied) {
            return;
        }

        $context = $denial->actorContext;
        $scope = $denial->resourceScope;
        $target = null !== $scope->storeId
            ? ResourceReference::for('store', $scope->storeId)
            : ResourceReference::for('organization', $scope->organizationId);
        $this->transaction->transactional($context->organizationId(), function () use ($context, $denial, $target): void {
            $this->audit->recordDenied(
                $context,
                $target,
                'Required permission was not granted.',
                SafeAuditMetadata::fromArray(['permission' => $denial->permission->value]),
                $this->clock->now(),
            );
        });

        $event->setResponse(new JsonResponse([
            'code' => 'AUTHORIZATION_DENIED',
            'message' => 'You are not authorized to perform this operation.',
            'correlationId' => $context->correlationId()->toString(),
        ], Response::HTTP_FORBIDDEN));
    }
}
