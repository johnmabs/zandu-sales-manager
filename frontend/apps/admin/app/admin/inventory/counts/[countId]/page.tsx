import { InventoryAreaRoute } from "../../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Inventaire physique"
      permission="STOCK_COUNT_READ"
      description="Le détail de l’inventaire sera livré par F4.15."
    />
  );
}
