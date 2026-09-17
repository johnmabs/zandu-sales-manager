"use client";

import { Button, Input, Select } from "@zandu/ui";
import { useId } from "react";

import { normalizeStockPositionFilters } from "./stockPositionFilters";

import type { ProductFilters } from "@zandu/api-client";

export function StockPositionFiltersForm({
  metadataAllowed,
  onApply,
  onReset,
}: Readonly<{
  metadataAllowed: boolean;
  onApply: (filters: ProductFilters) => void;
  onReset: () => void;
}>) {
  const id = useId();

  return (
    <form
      aria-label="Filtres des positions de stock"
      className="zandu-product-filters"
      onSubmit={(event) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        onApply(
          normalizeStockPositionFilters({
            search: String(form.get("search") ?? ""),
            status: String(form.get("status") ?? ""),
            type: String(form.get("type") ?? ""),
          }),
        );
      }}
    >
      <div>
        <label htmlFor={`${id}-search`}>Rechercher un produit</label>
        <Input
          id={`${id}-search`}
          disabled={!metadataAllowed}
          name="search"
          placeholder="Code ou nom du produit"
          type="search"
        />
      </div>
      <div>
        <label htmlFor={`${id}-status`}>Statut du produit</label>
        <Select id={`${id}-status`} defaultValue="" disabled={!metadataAllowed} name="status">
          <option value="">Tous les statuts</option>
          <option value="DRAFT">Brouillon</option>
          <option value="ACTIVE">Actif</option>
          <option value="INACTIVE">Inactif</option>
          <option value="ARCHIVED">Archivé</option>
        </Select>
      </div>
      <div>
        <label htmlFor={`${id}-type`}>Type de produit</label>
        <Select id={`${id}-type`} defaultValue="" disabled={!metadataAllowed} name="type">
          <option value="">Tous les types</option>
          <option value="PHYSICAL">Physique</option>
          <option value="SERVICE">Service</option>
        </Select>
      </div>
      <p>
        {metadataAllowed
          ? "Les filtres sont appliqués par le service Catalog."
          : "La recherche par produit nécessite la permission PRODUCT_READ."}
      </p>
      <Button disabled={!metadataAllowed} type="submit">
        Appliquer les filtres
      </Button>
      <Button type="reset" onClick={onReset} variant="secondary">
        Réinitialiser les filtres
      </Button>
    </form>
  );
}
