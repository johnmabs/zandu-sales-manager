"use client";

import { Button, Input } from "@zandu/ui";
import { useId, useState } from "react";

export function StockMovementFiltersForm({
  initialProductId,
  onApply,
  onReset,
}: Readonly<{
  initialProductId?: string;
  onApply: (productId: string | undefined) => void;
  onReset: () => void;
}>) {
  const id = useId();
  const [productId, setProductId] = useState(initialProductId ?? "");

  return (
    <form
      aria-label="Filtres des mouvements de stock"
      onSubmit={(event) => {
        event.preventDefault();
        const value = productId.trim();
        onApply(value === "" ? undefined : value);
      }}
    >
      <label htmlFor={`${id}-product`}>Identifiant produit</label>
      <Input
        id={`${id}-product`}
        name="productId"
        onChange={(event) => setProductId(event.currentTarget.value)}
        placeholder="Filtrer par produit"
        value={productId}
      />
      <p>Le filtre Produit et la pagination sont appliqués par le serveur Inventory.</p>
      <Button type="submit">Appliquer le filtre</Button>
      <Button
        type="button"
        variant="secondary"
        onClick={() => {
          setProductId("");
          onReset();
        }}
      >
        Réinitialiser le filtre
      </Button>
    </form>
  );
}
