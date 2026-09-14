"use client";
import { useMutation } from "@tanstack/react-query";
import { can, useEffectiveAccess } from "@zandu/authorization";
import { useOrganizationContext } from "@zandu/organization-context";
import { Button, EmptyState, Input, Spinner } from "@zandu/ui";
import { useState } from "react";

import { useAdminRuntime } from "../../../runtime/AdminRuntime";
import { useCategoryOptions } from "../hooks/useCategoryOptions";

import type { CategoryResource } from "@zandu/api-client";

export function CategoryManagement() {
  const access = useEffectiveAccess();
  const { activeOrganizationId } = useOrganizationContext();
  const { api, queryClient } = useAdminRuntime();
  const query = useCategoryOptions({ api, access, organizationId: activeOrganizationId });
  const [name, setName] = useState("");
  const refresh = () =>
    queryClient.invalidateQueries({ queryKey: ["categories", activeOrganizationId] });
  const create = useMutation({
    mutationFn: () => api!.createCategory({ name, parentCategoryId: null }),
    onSuccess: async () => {
      setName("");
      await refresh();
    },
  });
  const mutate = async (category: CategoryResource, action: string) => {
    if (action === "rename") {
      const value = window.prompt("Nouveau nom", category.name);
      if (value) await api!.updateCategory(category.id, { name: value });
    } else if (action === "move") {
      const value = window.prompt(
        "Identifiant de la catégorie parente (vide pour racine)",
        category.parentCategoryId ?? "",
      );
      await api!.moveCategory(category.id, value || null);
    } else {
      if (action === "archive" && !window.confirm("Archiver cette catégorie ?")) return;
      if (action === "activate" || action === "archive" || action === "deactivate") {
        await api!.transitionCategory(category.id, action);
      }
    }
    await refresh();
  };
  if (query.isLoading) return <Spinner label="Chargement des catégories" />;
  const categories = query.data ?? [];
  const children = (parent: string | null) =>
    categories.filter((c) => c.parentCategoryId === parent);
  const branch = (parent: string | null): React.ReactNode => (
    <ul>
      {children(parent).map((c) => (
        <li key={c.id}>
          <strong>{c.name}</strong> — {c.status}
          {can(access, "CATEGORY_UPDATE", { organizationId: activeOrganizationId! }) ? (
            <>
              <Button onClick={() => void mutate(c, "rename")}>Renommer</Button>
              <Button onClick={() => void mutate(c, "move")}>Déplacer</Button>
              {c.status === "ACTIVE" ? (
                <Button onClick={() => void mutate(c, "deactivate")}>Désactiver</Button>
              ) : (
                <Button onClick={() => void mutate(c, "activate")}>Activer</Button>
              )}
            </>
          ) : null}
          {can(access, "CATEGORY_ARCHIVE", { organizationId: activeOrganizationId! }) &&
          c.status !== "ARCHIVED" ? (
            <Button onClick={() => void mutate(c, "archive")}>Archiver</Button>
          ) : null}
          {branch(c.id)}
        </li>
      ))}
    </ul>
  );
  return (
    <div>
      {categories.length ? (
        branch(null)
      ) : (
        <EmptyState
          title="Aucune catégorie"
          description="Créez une première catégorie pour structurer le catalogue."
        />
      )}
      {activeOrganizationId &&
      can(access, "CATEGORY_CREATE", { organizationId: activeOrganizationId }) ? (
        <form
          onSubmit={(e) => {
            e.preventDefault();
            create.mutate();
          }}
        >
          <label>
            Nom
            <Input value={name} onChange={(e) => setName(e.target.value)} required />
          </label>
          <Button type="submit">Créer la catégorie</Button>
        </form>
      ) : null}
    </div>
  );
}
