import { StoreDetailsPage } from "../components/StoreDetailsPage";

export function StoreDetailsRoute({ storeId }: Readonly<{ storeId: string }>) {
  return <StoreDetailsPage storeId={storeId} />;
}
