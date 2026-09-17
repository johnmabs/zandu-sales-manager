import { InventorySection } from "../components/InventorySection";

export function InventoryAreaRoute({
  description,
  permission,
  title,
}: Readonly<{ description: string; permission: string; title: string }>) {
  return (
    <InventorySection title={title} permission={permission}>
      <p>{description}</p>
    </InventorySection>
  );
}
