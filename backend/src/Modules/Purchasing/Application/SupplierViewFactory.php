<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;

final readonly class SupplierViewFactory
{
    public function create(Supplier $supplier): SupplierView
    {
        return new SupplierView(
            $supplier->id()->toString(),
            $supplier->name()->value(),
            $supplier->phone(),
            $supplier->email(),
            $supplier->address(),
            $supplier->notes(),
            $supplier->status()->value,
            $supplier->createdAt()->format(DATE_ATOM),
            $supplier->updatedAt()?->format(DATE_ATOM),
            $supplier->version(),
        );
    }
}
