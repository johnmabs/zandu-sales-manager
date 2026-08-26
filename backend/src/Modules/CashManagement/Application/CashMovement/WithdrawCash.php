<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Application\CashMovement;
use Zandu\SharedKernel\Context\ActorContext;use Zandu\SharedKernel\Identity\{CashSessionId,StoreId};use Zandu\SharedKernel\Money\Money;
final readonly class WithdrawCash{public function __construct(public StoreId $storeId,public CashSessionId $sessionId,public Money $amount,public string $reason,public ActorContext $actorContext){}}
