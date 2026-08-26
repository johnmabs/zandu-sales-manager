<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\Modules\Pricing\Application\ActivatePriceList\{ActivatePriceList,ActivatePriceListHandler};
use Zandu\Modules\Pricing\Application\{ArchivePriceList,ArchivePriceListHandler,CreatePriceList,CreatePriceListHandler,DeactivatePriceList,DeactivatePriceListHandler,UpdatePriceList,UpdatePriceListHandler};
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\UuidFactory;

/** @implements ProcessorInterface<mixed, PriceListResource> */
final readonly class PriceListProcessor implements ProcessorInterface
{
    public function __construct(private CurrentActorProvider $actors, private UuidFactory $uuids, private CreatePriceListHandler $create, private UpdatePriceListHandler $update, private ActivatePriceListHandler $activate, private DeactivatePriceListHandler $deactivate, private ArchivePriceListHandler $archive) {} public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PriceListResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        $parse = static fn(?string $v): ?DateTimeImmutable => null === $v ? null : new DateTimeImmutable($v);
        if ('price_list_create' === $name) {
            $i = $data instanceof PriceListCreateInput ? $data : throw new InvalidArgumentException('Price list input is required.');
            $p = ($this->create)(new CreatePriceList($i->code, $i->name, $i->currency, $parse($i->validFrom), $parse($i->validTo), $i->priority, $actor));
        } elseif ('price_list_update' === $name) {
            $i = $data instanceof PriceListUpdateInput ? $data : throw new InvalidArgumentException('Price list input is required.');
            $p = ($this->update)(new UpdatePriceList(PriceListId::fromString($this->id($uriVariables), $this->uuids), $i->code, $i->name, $parse($i->validFrom), $parse($i->validTo), $i->priority, $actor));
        } else {
            $id = PriceListId::fromString($this->id($uriVariables), $this->uuids);
            $p = match ($name) {
                'price_list_activate' => ($this->activate)(new ActivatePriceList($id, $actor)),
                'price_list_deactivate' => ($this->deactivate)(new DeactivatePriceList($id, $actor)),
                'price_list_archive' => ($this->archive)(new ArchivePriceList($id, $actor)),
                default => throw new InvalidArgumentException('Unsupported price list operation.'),
            };
        }return new PriceListResource($p->id()->toString(), $p->organizationId()->toString(), $p->code()->value(), $p->name()->value(), $p->currency()->code(), $p->status()->value, $p->scope()->value, $p->validFrom()?->format(DATE_ATOM), $p->validTo()?->format(DATE_ATOM), $p->priority()->value(), $p->createdAt()->format(DATE_ATOM), $p->version());
    } /** @param array<string, mixed> $v */ private function id(array $v): string
    {
        $x = $v['id'] ?? null;
        return is_string($x) ? $x : throw new InvalidArgumentException('Price list identifier is required.');
    }
}
