import { InventoryAreaRoute } from "../../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Nouveau transfert"
      permission="STOCK_TRANSFER_CREATE"
      description="La création de transfert sera livrée par F4.11."
    />
  );
}
