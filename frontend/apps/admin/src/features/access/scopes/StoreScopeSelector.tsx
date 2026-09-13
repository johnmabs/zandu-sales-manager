"use client";

import { Checkbox } from "@zandu/ui";

import type { StoreResource } from "@zandu/api-client";

export type StoreScopeType = "ORGANIZATION" | "SELECTED_STORES";

type StoreScopeSelectorProperties = Readonly<{
  error?: string | undefined;
  isLoading: boolean;
  onRetry?: (() => void) | undefined;
  onScopeTypeChange: (scopeType: StoreScopeType) => void;
  onSelectedStoreIdsChange: (storeIds: readonly string[]) => void;
  scopeType: StoreScopeType;
  selectedStoreIds: readonly string[];
  stores?: readonly StoreResource[] | undefined;
}>;

/**
 * Collects a scope intention for a role assignment. It deliberately does not
 * infer permissions or decide whether a selected Store is valid: Symfony is
 * authoritative for both decisions.
 */
export function StoreScopeSelector({
  error,
  isLoading,
  onRetry,
  onScopeTypeChange,
  onSelectedStoreIdsChange,
  scopeType,
  selectedStoreIds,
  stores,
}: StoreScopeSelectorProperties) {
  const selectableStores = stores ?? [];
  const canSelectStores = !isLoading && error === undefined && selectableStores.length > 0;

  const toggleStore = (storeId: string, selected: boolean) => {
    const nextStoreIds = selected
      ? [...selectedStoreIds, storeId]
      : selectedStoreIds.filter((selectedStoreId) => selectedStoreId !== storeId);

    onSelectedStoreIdsChange(nextStoreIds);
  };

  return (
    <fieldset className="zandu-store-scope-selector">
      <legend>Portée des rôles</legend>
      <label>
        <input
          checked={scopeType === "ORGANIZATION"}
          name="store-scope"
          onChange={() => onScopeTypeChange("ORGANIZATION")}
          type="radio"
        />
        Toute l’organisation
      </label>
      <label>
        <input
          checked={scopeType === "SELECTED_STORES"}
          disabled={!canSelectStores}
          name="store-scope"
          onChange={() => onScopeTypeChange("SELECTED_STORES")}
          type="radio"
        />
        Magasins sélectionnés
      </label>
      {isLoading ? <p role="status">Chargement des magasins accessibles</p> : null}
      {error === undefined ? null : (
        <p role="alert">
          Les magasins accessibles n’ont pas pu être chargés.
          {onRetry === undefined ? null : (
            <button onClick={onRetry} type="button">
              Réessayer
            </button>
          )}
        </p>
      )}
      {!isLoading && error === undefined && selectableStores.length === 0 ? (
        <p>Aucun magasin accessible ne peut être sélectionné.</p>
      ) : null}
      {scopeType !== "SELECTED_STORES" ? null : (
        <fieldset>
          <legend>Magasins concernés</legend>
          {selectableStores.map((store) => (
            <Checkbox
              checked={selectedStoreIds.includes(store.id)}
              key={store.id}
              label={`${store.name} (${store.code})`}
              onChange={(event) => toggleStore(store.id, event.currentTarget.checked)}
            />
          ))}
        </fieldset>
      )}
      <p>Cette sélection prépare une demande de portée ; elle n’accorde aucun droit.</p>
    </fieldset>
  );
}
