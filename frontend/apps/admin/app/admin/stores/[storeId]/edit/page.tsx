import { EditStoreRoute } from "../../../../../src/features/stores";

export default async function EditStore({
  params,
}: Readonly<{ params: Promise<{ storeId: string }> }>) {
  const { storeId } = await params;

  return <EditStoreRoute storeId={storeId} />;
}
