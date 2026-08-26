<?php
declare(strict_types=1);
namespace Zandu\Modules\CashManagement\Infrastructure\Persistence\Orm;
use DateTimeImmutable; use Doctrine\ORM\Mapping as ORM; use Zandu\Modules\CashManagement\Domain\CashRegister\CashRegister;
#[ORM\Entity] #[ORM\Table(name:'cash_register',schema:'cash_management')]
final class CashRegisterRecord
{
 private function __construct(#[ORM\Id] #[ORM\Column(type:'guid')] private string $id,#[ORM\Column(type:'guid')] private string $organizationId,#[ORM\Column(type:'guid')] private string $storeId,#[ORM\Column(length:32)] private string $code,#[ORM\Column(length:160)] private string $name,#[ORM\Column(length:16)] private string $status,#[ORM\Column(type:'datetimetz_immutable')] private DateTimeImmutable $createdAt,#[ORM\Column(type:'guid')] private string $createdBy,#[ORM\Column(type:'datetimetz_immutable',nullable:true)] private ?DateTimeImmutable $updatedAt,#[ORM\Column(type:'guid',nullable:true)] private ?string $updatedBy,#[ORM\Version] #[ORM\Column(type:'integer')] private int $version){}
 public static function fromAggregate(CashRegister $r):self{return new self($r->id()->toString(),$r->organizationId()->toString(),$r->storeId()->toString(),$r->code(),$r->name(),$r->status()->value,$r->createdAt(),$r->createdBy()->toString(),$r->updatedAt(),$r->updatedBy()?->toString(),$r->version());}
 public function synchronize(CashRegister $r):void{$this->code=$r->code();$this->name=$r->name();$this->status=$r->status()->value;$this->updatedAt=$r->updatedAt();$this->updatedBy=$r->updatedBy()?->toString();}
 public function id():string{return $this->id;} public function organizationId():string{return $this->organizationId;} public function storeId():string{return $this->storeId;} public function code():string{return $this->code;} public function name():string{return $this->name;} public function status():string{return $this->status;} public function createdAt():DateTimeImmutable{return $this->createdAt;} public function createdBy():string{return $this->createdBy;} public function updatedAt():?DateTimeImmutable{return $this->updatedAt;} public function updatedBy():?string{return $this->updatedBy;} public function version():int{return $this->version;}
}
