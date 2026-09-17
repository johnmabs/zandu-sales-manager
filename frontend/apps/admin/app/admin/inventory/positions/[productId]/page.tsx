import { InventoryAreaRoute } from "../../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Position de stock"
      permission="INVENTORY_READ"
      description="Le détail de la position sera livré par F4.4."
    />
  );
}
