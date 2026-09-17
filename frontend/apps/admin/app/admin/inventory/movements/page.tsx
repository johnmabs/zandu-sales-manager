import { InventoryAreaRoute } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Mouvements de stock"
      permission="STOCK_MOVEMENT_READ"
      description="L’historique des mouvements sera livré par F4.7."
    />
  );
}
