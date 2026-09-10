import { EditStorePage } from "../components/EditStorePage";

export function EditStoreRoute({ storeId }: Readonly<{ storeId: string }>) {
  return <EditStorePage storeId={storeId} />;
}
