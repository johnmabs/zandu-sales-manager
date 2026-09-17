import { InventoryAreaRoute } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Inventaires physiques"
      permission="STOCK_COUNT_READ"
      description="La liste des inventaires sera livrée par F4.15."
    />
  );
}
