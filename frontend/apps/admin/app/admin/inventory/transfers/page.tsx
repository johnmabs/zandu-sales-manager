import { InventorySection, StockTransfersPage } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventorySection title="Transferts de stock" permission="STOCK_TRANSFER_READ">
      <StockTransfersPage />
    </InventorySection>
  );
}
