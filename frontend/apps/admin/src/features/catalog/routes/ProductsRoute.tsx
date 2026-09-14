import { CatalogSection } from "../components/CatalogSection";

export function ProductsRoute() {
  return (
    <CatalogSection title="Produits" permission="PRODUCT_READ">
      <p>L’administration de cette section sera disponible prochainement.</p>
    </CatalogSection>
  );
}
