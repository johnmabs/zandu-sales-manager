import { InventorySection, StockMovementHistoryPage } from "../../../../src/features/inventory";

export default async function Page({
  searchParams,
}: Readonly<{ searchParams: Promise<{ productId?: string | string[] }> }>) {
  const query = await searchParams;
  const productId = typeof query.productId === "string" ? query.productId.trim() : undefined;

  return (
    <InventorySection title="Mouvements de stock" permission="STOCK_MOVEMENT_READ">
      <StockMovementHistoryPage
        {...(productId === undefined || productId === "" ? {} : { initialProductId: productId })}
      />
    </InventorySection>
  );
}
