import { InventorySection, StockPositionsPage } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventorySection title="Positions de stock" permission="INVENTORY_READ">
      <StockPositionsPage />
    </InventorySection>
  );
}
