import { InventoryAreaRoute } from "../../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Transfert de stock"
      permission="STOCK_TRANSFER_READ"
      description="Le détail du transfert sera livré par F4.10."
    />
  );
}
