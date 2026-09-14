import { CatalogSection } from "../components/CatalogSection";

export function CategoriesRoute() {
  return (
    <CatalogSection title="Catégories" permission="CATALOG_READ">
      <p>L’administration de cette section sera disponible prochainement.</p>
    </CatalogSection>
  );
}
