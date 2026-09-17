import { InventorySection, StockPositionDetailsPage } from "../../../../../src/features/inventory";

export default async function Page({
  params,
}: Readonly<{ params: Promise<{ productId: string }> }>) {
  const { productId } = await params;

  return (
    <InventorySection title="Position de stock" permission="INVENTORY_READ">
      <StockPositionDetailsPage productId={productId} />
    </InventorySection>
  );
}
