import { StoreDetailsRoute } from "../../../../src/features/stores/routes/StoreDetailsRoute";

export default async function StoreDetailsPage({
  params,
}: Readonly<{ params: Promise<{ storeId: string }> }>) {
  const { storeId } = await params;

  return <StoreDetailsRoute storeId={storeId} />;
}
