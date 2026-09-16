<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Zandu\Modules\Catalog\Application\ActivateCategory\ActivateCategory;
use Zandu\Modules\Catalog\Application\ActivateCategory\ActivateCategoryHandler;
use Zandu\Modules\Catalog\Application\ArchiveCategory\ArchiveCategory;
use Zandu\Modules\Catalog\Application\ArchiveCategory\ArchiveCategoryHandler;
use Zandu\Modules\Catalog\Application\CategoryViewFactory;
use Zandu\Modules\Catalog\Application\CreateCategory\CreateCategory;
use Zandu\Modules\Catalog\Application\CreateCategory\CreateCategoryHandler;
use Zandu\Modules\Catalog\Application\DeactivateCategory\DeactivateCategory;
use Zandu\Modules\Catalog\Application\DeactivateCategory\DeactivateCategoryHandler;
use Zandu\Modules\Catalog\Application\MoveCategory\MoveCategory;
use Zandu\Modules\Catalog\Application\MoveCategory\MoveCategoryHandler;
use Zandu\Modules\Catalog\Application\UpdateCategory\UpdateCategory;
use Zandu\Modules\Catalog\Application\UpdateCategory\UpdateCategoryHandler;
use Zandu\SharedKernel\Context\CurrentActorProvider;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

/** @implements ProcessorInterface<mixed, CategoryResource> */
final readonly class CategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private CurrentActorProvider $actors,
        private UuidFactory $uuidFactory,
        private CategoryViewFactory $views,
        private CategoryResourceFactory $resources,
        private CreateCategoryHandler $create,
        private UpdateCategoryHandler $update,
        private MoveCategoryHandler $move,
        private ActivateCategoryHandler $activate,
        private DeactivateCategoryHandler $deactivate,
        private ArchiveCategoryHandler $archive,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CategoryResource
    {
        $actor = $this->actors->resolve();
        $name = $operation->getName();
        if ('category_create' === $name) {
            $input = $data instanceof CategoryCreateInput ? $data : throw new InvalidArgumentException('Category creation input is required.');
            $category = ($this->create)(new CreateCategory($input->name, $this->nullableId($input->parentCategoryId), $actor));

            return $this->resources->fromView($this->views->fromAggregate($category));
        }

        $id = CategoryId::fromString($this->id($uriVariables), $this->uuidFactory);
        if ('category_update' === $name) {
            $input = $data instanceof CategoryUpdateInput ? $data : throw new InvalidArgumentException('Category update input is required.');
            $category = ($this->update)(new UpdateCategory($id, $input->name, ExpectedVersion::fromInt($input->expectedVersion), $actor));

            return $this->resources->fromView($this->views->fromAggregate($category));
        }
        if ('category_move' === $name) {
            $input = $data instanceof CategoryMoveInput ? $data : throw new InvalidArgumentException('Category move input is required.');
            $category = ($this->move)(new MoveCategory($id, $this->nullableId($input->parentCategoryId), $actor));

            return $this->resources->fromView($this->views->fromAggregate($category));
        }

        $category = match ($name) {
            'category_activate' => ($this->activate)(new ActivateCategory($id, $actor)),
            'category_deactivate' => ($this->deactivate)(new DeactivateCategory($id, $actor)),
            'category_archive' => ($this->archive)(new ArchiveCategory($id, $actor)),
            default => throw new InvalidArgumentException('Unsupported category operation.'),
        };

        return $this->resources->fromView($this->views->fromAggregate($category));
    }

    /** @param array<string,mixed> $uriVariables */
    private function id(array $uriVariables): string
    {
        $id = $uriVariables['id'] ?? null;

        return is_string($id) ? $id : throw new InvalidArgumentException('Category identifier is required.');
    }

    private function nullableId(?string $id): ?CategoryId
    {
        return null !== $id ? CategoryId::fromString($id, $this->uuidFactory) : null;
    }
}
