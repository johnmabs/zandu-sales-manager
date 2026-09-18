import { InventorySection, InventoryValuationsPage } from "../../../../src/features/inventory";

export default async function Page({
  searchParams,
}: Readonly<{ searchParams: Promise<{ productId?: string | string[] }> }>) {
  const query = await searchParams;
  const productId = typeof query.productId === "string" ? query.productId.trim() : undefined;

  return (
    <InventorySection title="Valorisation du stock" permission="INVENTORY_READ">
      <InventoryValuationsPage
        {...(productId === undefined || productId === "" ? {} : { productId })}
      />
    </InventorySection>
  );
}
