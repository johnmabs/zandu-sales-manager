import { InventoryAreaRoute } from "../../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Nouvel inventaire"
      permission="STOCK_COUNT_CREATE"
      description="La création d’inventaire sera livrée par F4.16."
    />
  );
}
