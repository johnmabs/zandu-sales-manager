<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Domain\CashRegister;
use Zandu\SharedKernel\Identity\{OrganizationId,StoreId,CashRegisterId};
interface CashRegisterRepository { public function save(CashRegister $register): void; public function find(OrganizationId $organizationId, StoreId $storeId, CashRegisterId $id): ?CashRegister; public function findById(OrganizationId $organizationId, CashRegisterId $id): ?CashRegister; /** @return list<CashRegister> */ public function findAll(OrganizationId $organizationId, StoreId $storeId): array; }
