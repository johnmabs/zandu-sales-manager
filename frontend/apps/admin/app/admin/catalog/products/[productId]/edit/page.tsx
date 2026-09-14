import { EditProductPage } from "../../../../../../src/features/catalog/products/EditProductPage";
export default async function Page({
  params,
}: Readonly<{ params: Promise<{ productId: string }> }>) {
  const { productId } = await params;
  return <EditProductPage productId={productId} />;
}
