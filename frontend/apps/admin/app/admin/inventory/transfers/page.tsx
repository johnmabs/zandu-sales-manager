import { InventoryAreaRoute } from "../../../../src/features/inventory";

export default function Page() {
  return (
    <InventoryAreaRoute
      title="Transferts de stock"
      permission="STOCK_TRANSFER_READ"
      description="La liste des transferts sera livrée par F4.10."
    />
  );
}
