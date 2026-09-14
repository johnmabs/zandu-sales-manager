import { cleanup, render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { ProductCreateForm } from "../../apps/admin/src/features/catalog/products/ProductCreateForm";
import { ApiRequestError } from "../../packages/api-client/src/index";

afterEach(cleanup);

describe("Product creation form", () => {
  it("validates structural fields and submits only the OpenAPI payload", async () => {
    const onCreate = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(<ProductCreateForm onCreate={onCreate} />);

    await user.click(screen.getByRole("button", { name: "Créer le produit" }));
    expect(await screen.findByText("Le code est requis.")).toBeTruthy();
    expect(screen.getByText("Le nom est requis.")).toBeTruthy();
    expect(screen.getByText("L’unité de base est requise.")).toBeTruthy();

    await user.type(screen.getByLabelText("Code"), " MED-001 ");
    await user.type(screen.getByLabelText("Nom"), " Paracétamol 500 mg ");
    await user.type(screen.getByLabelText("Description"), " Antalgique ");
    await user.type(screen.getByLabelText("Identifiant de l’unité de base"), " unit-1 ");
    await user.type(screen.getByLabelText("Identifiant de catégorie"), " category-1 ");
    await user.click(screen.getByRole("button", { name: "Créer le produit" }));

    await waitFor(() =>
      expect(onCreate).toHaveBeenCalledWith({
        baseUnitId: "unit-1",
        categoryId: "category-1",
        description: "Antalgique",
        inventoryTracked: true,
        name: "Paracétamol 500 mg",
        productCode: "MED-001",
        taxCategoryId: null,
        type: "PHYSICAL",
      }),
    );
  });

  it("maps server field errors and preserves the correlation ID", async () => {
    const onCreate = vi.fn().mockRejectedValue(
      new ApiRequestError(
        {
          correlationId: "product-ref",
          fieldErrors: { productCode: ["Code invalide côté serveur."] },
          kind: "response",
          status: 422,
        },
        false,
      ),
    );
    const user = userEvent.setup();
    render(<ProductCreateForm onCreate={onCreate} />);
    await user.type(screen.getByLabelText("Code"), "MED-001");
    await user.type(screen.getByLabelText("Nom"), "Paracétamol");
    await user.type(screen.getByLabelText("Identifiant de l’unité de base"), "unit-1");
    await user.click(screen.getByRole("button", { name: "Créer le produit" }));
    expect(await screen.findByText("Code invalide côté serveur.")).toBeTruthy();
    expect(screen.getByText("Référence de diagnostic : product-ref")).toBeTruthy();
  });

  it("blocks a blind retry when the POST result is unknown", async () => {
    const onCreate = vi
      .fn()
      .mockRejectedValue(
        new ApiRequestError({ kind: "network", message: "The request timed out." }, true),
      );
    const user = userEvent.setup();
    render(<ProductCreateForm onCreate={onCreate} />);
    await user.type(screen.getByLabelText("Code"), "MED-001");
    await user.type(screen.getByLabelText("Nom"), "Paracétamol");
    await user.type(screen.getByLabelText("Identifiant de l’unité de base"), "unit-1");
    await user.click(screen.getByRole("button", { name: "Créer le produit" }));
    const button = await screen.findByRole("button", { name: "Résultat à vérifier" });
    expect(button.hasAttribute("disabled")).toBe(true);
    await user.click(button);
    expect(onCreate).toHaveBeenCalledTimes(1);
  });
});
