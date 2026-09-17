import { InventoryAreaRoute } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Valorisation du stock"
      permission="INVENTORY_READ"
      description="Les vues de valorisation seront livrées par F4.8."
    />
  );
}
