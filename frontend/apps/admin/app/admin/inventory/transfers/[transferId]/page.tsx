import { InventorySection, StockTransferDetailsPage } from "../../../../../src/features/inventory";

export default async function Page({
  params,
}: Readonly<{ params: Promise<{ transferId: string }> }>) {
  const { transferId } = await params;
  return (
    <InventorySection title="Transfert de stock" permission="STOCK_TRANSFER_READ">
      <StockTransferDetailsPage transferId={transferId} />
    </InventorySection>
  );
}
