import { InventoryAreaRoute } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Positions de stock"
      permission="INVENTORY_READ"
      description="Les positions de stock seront livrées par F4.3."
    />
  );
}
