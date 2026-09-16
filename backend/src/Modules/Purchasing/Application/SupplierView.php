<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

final readonly class SupplierView
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt,
        public int $version,
    ) {}
}
