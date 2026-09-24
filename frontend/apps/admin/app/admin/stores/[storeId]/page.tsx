import { StoreDetailsRoute } from "../../../../src/features/stores";

export default async function StoreDetailsPage({
  params,
}: Readonly<{ params: Promise<{ storeId: string }> }>) {
  const { storeId } = await params;

  return <StoreDetailsRoute storeId={storeId} />;
}
