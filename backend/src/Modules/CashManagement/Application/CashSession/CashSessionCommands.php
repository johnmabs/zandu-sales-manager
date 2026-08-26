<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Application\CashSession;
use Zandu\SharedKernel\Context\ActorContext;use Zandu\SharedKernel\Identity\{CashRegisterId,CashSessionId,StoreId};use Zandu\SharedKernel\Money\Money;
final readonly class OpenCashSession{public function __construct(public StoreId $storeId,public CashRegisterId $cashRegisterId,public Money $openingBalance,public ActorContext $actorContext){}}
final readonly class CloseCashSession{public function __construct(public StoreId $storeId,public CashSessionId $sessionId,public Money $countedBalance,public Money $expectedBalance,public ActorContext $actorContext){}}
