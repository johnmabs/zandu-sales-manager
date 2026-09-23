import { CreateStockTransferPage, InventorySection } from "../../../../../src/features/inventory";

export default function Page() {
  return (
    <InventorySection title="Nouveau transfert" permission="STOCK_TRANSFER_CREATE">
      <CreateStockTransferPage />
    </InventorySection>
  );
}
