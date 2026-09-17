import { CatalogSection } from "../components/CatalogSection";
import { ProductListPage } from "../products/ProductListPage";

export function ProductsRoute() {
  return (
    <CatalogSection title="Produits" permission="PRODUCT_READ">
      <p>
        <a href="/admin/catalog/products/new">Nouveau produit</a>
      </p>
      <ProductListPage />
    </CatalogSection>
  );
}
