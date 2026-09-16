<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use Zandu\Modules\Purchasing\Application\SupplierView;

final readonly class SupplierResourceMapper
{
    public function map(SupplierView $view): SupplierResource
    {
        return new SupplierResource($view->id, $view->name, $view->phone, $view->email, $view->address, $view->notes, $view->status, $view->createdAt, $view->updatedAt, $view->version);
    }
}
