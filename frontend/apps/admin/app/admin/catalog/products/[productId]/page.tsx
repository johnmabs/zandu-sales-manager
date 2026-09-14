import { ProductDetailsPage } from "../../../../../src/features/catalog/products/ProductDetailsPage";
export default async function Page({
  params,
}: Readonly<{ params: Promise<{ productId: string }> }>) {
  const { productId } = await params;
  return <ProductDetailsPage productId={productId} />;
}
