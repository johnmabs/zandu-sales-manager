<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

final readonly class SaleView
{
    /** @param array{id:string,storeId:string,status:string,currency:string,lines:list<array<string,mixed>>,subtotal:string,discountTotal:string,taxTotal:string,total:string,businessDate:?string,completedAt:?string,version:int} $data */
    public function __construct(public array $data) {}
}
