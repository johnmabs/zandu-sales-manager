<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Infrastructure\Persistence\Orm;
use Doctrine\ORM\{EntityManagerInterface,OptimisticLockException}; use Zandu\Modules\CashManagement\Domain\CashRegister\{CashRegister,CashRegisterRepository,CashRegisterStatus}; use Zandu\SharedKernel\Identity\{ActorId,CashRegisterId,OrganizationId,StoreId,UuidFactory};
final readonly class DoctrineCashRegisterRepository implements CashRegisterRepository
{
 public function __construct(private EntityManagerInterface $em,private UuidFactory $uuids){}
 public function save(CashRegister $r):void{$record=$this->em->find(CashRegisterRecord::class,$r->id()->toString());if($record instanceof CashRegisterRecord){$expected=$r->version()-1;if($record->version()!==$expected)throw OptimisticLockException::lockFailedVersionMismatch($record,$expected,$record->version());$record->synchronize($r);}else $this->em->persist(CashRegisterRecord::fromAggregate($r));$this->em->flush();}
 public function find(OrganizationId $o,StoreId $s,CashRegisterId $id):?CashRegister{return $this->aggregate($this->em->getRepository(CashRegisterRecord::class)->findOneBy(['organizationId'=>$o->toString(),'storeId'=>$s->toString(),'id'=>$id->toString()]));}
 public function findById(OrganizationId $o,CashRegisterId $id):?CashRegister{return $this->aggregate($this->em->getRepository(CashRegisterRecord::class)->findOneBy(['organizationId'=>$o->toString(),'id'=>$id->toString()]));}
 public function findAll(OrganizationId $o,StoreId $s):array{return array_values(array_filter(array_map(fn($r)=>$this->aggregate($r),$this->em->getRepository(CashRegisterRecord::class)->findBy(['organizationId'=>$o->toString(),'storeId'=>$s->toString()],['code'=>'ASC']))));}
 private function aggregate(mixed $v):?CashRegister{if(!$v instanceof CashRegisterRecord)return null;$f=$this->uuids;return CashRegister::reconstitute(CashRegisterId::fromString($v->id(),$f),OrganizationId::fromString($v->organizationId(),$f),StoreId::fromString($v->storeId(),$f),$v->code(),$v->name(),CashRegisterStatus::from($v->status()),$v->createdAt(),ActorId::fromString($v->createdBy(),$f),$v->updatedAt(),null===$v->updatedBy()?null:ActorId::fromString($v->updatedBy(),$f),$v->version());}
}
