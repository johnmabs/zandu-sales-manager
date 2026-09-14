import { CategoryManagement } from "../categories/CategoryManagement";
import { CatalogSection } from "../components/CatalogSection";

export function CategoriesRoute() {
  return (
    <CatalogSection title="Catégories" permission="CATALOG_READ">
      <CategoryManagement />
    </CatalogSection>
  );
}
