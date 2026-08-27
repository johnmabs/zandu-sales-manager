<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use LogicException;
use Throwable;
use Zandu\Modules\Catalog\Application\Contract\SaleProductProvider;
use Zandu\Modules\Sales\Application\Contract\SaleTaxPolicy;
use Zandu\Modules\Sales\Domain\{Sale,SaleLine};
use Zandu\SharedKernel\Decimal\{DecimalFactory,RoundingMode};
use Zandu\SharedKernel\Identity\{IdGenerator,ProductId,ProductPackagingId,SaleLineId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Time\Clock;

final readonly class SaleLineFactory
{
    public function __construct(private SaleProductProvider $products, private SalePricingService $pricing, private SalePricingCalculator $calculator, private SaleTaxPolicy $taxes, private DecimalFactory $decimals, private IdGenerator $ids, private Clock $clock) {}

    public function create(Sale $sale, ProductId $productId, ProductPackagingId $packagingId, Quantity $quantity, ?SaleLineId $lineId = null): SaleLine
    {
        $product = $this->products->provide($sale->organizationId(), $productId, $packagingId);
        $this->assertQuantity($quantity, $product->minimumQuantity, $product->quantityIncrement, $product->quantityPrecision);
        $price = $this->pricing->resolve($sale->organizationId(), $productId, $packagingId, $this->clock->now());
        if ($price->currency()->code() !== $sale->currency()) {
            throw new LogicException('Product price currency must match sale currency.');
        }
        $unitPrice = new Money($price->priceAmount(), $price->currency());
        $subtotal = $this->calculator->calculateLineTotal($quantity, $unitPrice);
        $zero = new Money($this->decimals->fromString('0'), $price->currency());
        $tax = $this->taxes->calculate($subtotal);

        return new SaleLine(
            $lineId ?? SaleLineId::generate($this->ids),
            $sale->id(),
            $product->productId,
            $product->productPackagingId,
            $product->productCode,
            $product->productName,
            $product->packagingCode,
            $product->packagingName,
            $product->unitId,
            $quantity,
            $product->conversionFactor,
            $quantity->multiply($product->conversionFactor->value(), 12, RoundingMode::HalfUp),
            $unitPrice,
            $price->priceListId()->toString(),
            $price->productPriceId()->toString(),
            $zero,
            $tax->taxableAmount,
            $tax->taxAmount,
            $subtotal,
            $subtotal->add($tax->taxAmount),
            ['product' => $product->sourceVersion, 'packaging' => $price->packagingSourceVersion(), 'priceList' => $price->priceListSourceVersion(), 'productPrice' => $price->productPriceSourceVersion(), 'taxPolicy' => $tax->policyCode],
        );
    }

    private function assertQuantity(Quantity $quantity, ?Quantity $minimum, ?Quantity $increment, int $precision): void
    {
        if ($quantity->isZero() || $quantity->isNegative()) {
            throw new LogicException('Sale line quantity must be greater than zero.');
        }
        if (null !== $minimum && $quantity->compareTo($minimum) < 0) {
            throw new LogicException('Sale line quantity is below the packaging minimum.');
        }
        $fraction = strchr($quantity->toString(), '.');
        if (false !== $fraction && strlen(rtrim(substr($fraction, 1), '0')) > $precision) {
            throw new LogicException('Sale line quantity exceeds packaging precision.');
        }
        if (null !== $increment) {
            try {
                $quantity->divide($increment->value(), 0, RoundingMode::Unnecessary);
            } catch (Throwable $exception) {
                throw new LogicException('Sale line quantity must respect the packaging increment.', previous: $exception);
            }
        }
    }
}
