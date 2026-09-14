"use client";
import { Button, Input, Select } from "@zandu/ui";
import { useId } from "react";

import { normalizeProductFilters } from "../schemas/productFilters";

import type { CategoryResource, ProductFilters } from "@zandu/api-client";

export function ProductFiltersForm({
  categories,
  categoryAllowed,
  categoryLoading,
  categoryError,
  onRetryCategories,
  onApply,
  onReset,
}: Readonly<{
  categories: readonly CategoryResource[];
  categoryAllowed: boolean;
  categoryLoading: boolean;
  categoryError: boolean;
  onRetryCategories: () => void;
  onApply: (filters: ProductFilters) => void;
  onReset: () => void;
}>) {
  const id = useId();
  return (
    <form
      aria-label="Filtres des produits"
      className="zandu-product-filters"
      onSubmit={(event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        onApply(
          normalizeProductFilters({
            search: String(form.get("search") ?? ""),
            status: String(form.get("status") ?? ""),
            type: String(form.get("type") ?? ""),
            categoryId: String(form.get("categoryId") ?? ""),
          }),
        );
      }}
    >
      <div>
        <label htmlFor={id + "-search"}>Rechercher un produit</label>
        <Input
          id={id + "-search"}
          name="search"
          type="search"
          placeholder="Code ou nom du produit"
        />
      </div>
      <div>
        <label htmlFor={id + "-status"}>Statut</label>
        <Select id={id + "-status"} name="status" defaultValue="">
          <option value="">Tous les statuts</option>
          <option value="DRAFT">Brouillon</option>
          <option value="ACTIVE">Actif</option>
          <option value="INACTIVE">Inactif</option>
          <option value="ARCHIVED">Archivé</option>
        </Select>
      </div>
      <div>
        <label htmlFor={id + "-type"}>Type</label>
        <Select id={id + "-type"} name="type" defaultValue="">
          <option value="">Tous les types</option>
          <option value="PHYSICAL">Physique</option>
          <option value="SERVICE">Service</option>
        </Select>
      </div>
      <div>
        <label htmlFor={id + "-category"}>Catégorie</label>
        <Select
          id={id + "-category"}
          aria-describedby={id + "-category-help"}
          name="categoryId"
          defaultValue=""
          disabled={!categoryAllowed || categoryLoading || categoryError}
        >
          <option value="">Toutes les catégories</option>
          {categories.map((category) => (
            <option key={category.id} value={category.id}>
              {category.name}
            </option>
          ))}
        </Select>
        <p id={id + "-category-help"}>
          {!categoryAllowed
            ? "La consultation des catégories nécessite la permission catalogue."
            : categoryLoading
              ? "Chargement des catégories…"
              : categoryError
                ? "Les catégories n’ont pas pu être chargées."
                : categories.length === 0
                  ? "Aucune catégorie disponible."
                  : "Filtrer par catégorie du catalogue."}
        </p>
        {categoryError && categoryAllowed ? (
          <Button type="button" onClick={onRetryCategories}>
            Réessayer les catégories
          </Button>
        ) : null}
      </div>
      <Button type="submit">Appliquer les filtres</Button>
      <Button type="reset" onClick={onReset} variant="secondary">
        Réinitialiser les filtres
      </Button>
    </form>
  );
}
