import { CatalogSection } from "../components/CatalogSection";
import { ProductListPage } from "../products/ProductListPage";

export function ProductsRoute() {
  return (
    <CatalogSection title="Produits" permission="PRODUCT_READ">
      <ProductListPage />
    </CatalogSection>
  );
}
