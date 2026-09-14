import { cleanup, render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { ProductUpdateForm } from "../../apps/admin/src/features/catalog/products/ProductUpdateForm";
import { ApiRequestError, type ProductResource } from "../../packages/api-client/src/index";

const product: ProductResource = {
  activatedAt: null,
  baseUnitId: "unit-1",
  categoryId: "category-1",
  createdAt: "2026-09-14T10:00:00Z",
  description: "Antalgique",
  id: "product-1",
  inventoryTracked: true,
  name: "Paracétamol",
  organizationId: "org-1",
  productCode: "MED-001",
  status: "DRAFT",
  taxCategoryId: null,
  type: "PHYSICAL",
  updatedAt: null,
  version: 3,
};

afterEach(cleanup);

describe("Product update form", () => {
  it("updates all OpenAPI fields while the product is DRAFT", async () => {
    const onUpdate = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(
      <ProductUpdateForm
        product={product}
        onUpdate={onUpdate}
        onConflictReload={() => undefined}
      />,
    );
    const code = screen.getByLabelText("Code");
    await user.clear(code);
    await user.type(code, "MED-002");
    const unit = screen.getByLabelText("Identifiant de l’unité de base");
    await user.clear(unit);
    await user.type(unit, "unit-2");
    await user.click(screen.getByRole("button", { name: "Enregistrer les modifications" }));
    await waitFor(() =>
      expect(onUpdate).toHaveBeenCalledWith(
        expect.objectContaining({
          productCode: "MED-002",
          baseUnitId: "unit-2",
          name: "Paracétamol",
        }),
      ),
    );
  });

  it("renders immutable business fields as text and excludes them after activation", async () => {
    const onUpdate = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(
      <ProductUpdateForm
        product={{ ...product, activatedAt: "2026-09-14T11:00:00Z", status: "ACTIVE" }}
        onUpdate={onUpdate}
        onConflictReload={() => undefined}
      />,
    );
    expect(screen.queryByLabelText("Code")).toBeNull();
    expect(screen.queryByLabelText("Identifiant de l’unité de base")).toBeNull();
    expect(screen.getByText("Code produit : MED-001")).toBeTruthy();
    expect(screen.getByText("Unité de base : unit-1")).toBeTruthy();
    await user.clear(screen.getByLabelText("Nom"));
    await user.type(screen.getByLabelText("Nom"), "Paracétamol 500 mg");
    await user.click(screen.getByRole("button", { name: "Enregistrer les modifications" }));
    await waitFor(() => expect(onUpdate).toHaveBeenCalled());
    const payload = onUpdate.mock.calls[0]?.[0];
    expect(payload).not.toHaveProperty("productCode");
    expect(payload).not.toHaveProperty("baseUnitId");
    expect(payload).toMatchObject({ name: "Paracétamol 500 mg" });
  });

  it("offers a server reload after a concurrent update", async () => {
    const reload = vi.fn();
    const onUpdate = vi
      .fn()
      .mockRejectedValue(
        new ApiRequestError(
          { correlationId: "conflict-ref", kind: "response", status: 409 },
          false,
        ),
      );
    const user = userEvent.setup();
    render(<ProductUpdateForm product={product} onUpdate={onUpdate} onConflictReload={reload} />);
    await user.click(screen.getByRole("button", { name: "Enregistrer les modifications" }));
    expect(
      await screen.findByText(
        "Le produit a changé entre-temps. Rechargez les données avant de réessayer.",
      ),
    ).toBeTruthy();
    expect(screen.getByText("Référence de diagnostic : conflict-ref")).toBeTruthy();
    await user.click(screen.getByRole("button", { name: "Recharger les données" }));
    expect(reload).toHaveBeenCalledOnce();
  });

  it("prevents a blind retry after an unknown PATCH result", async () => {
    const onUpdate = vi
      .fn()
      .mockRejectedValue(
        new ApiRequestError({ kind: "network", message: "The request timed out." }, true),
      );
    const user = userEvent.setup();
    render(
      <ProductUpdateForm
        product={product}
        onUpdate={onUpdate}
        onConflictReload={() => undefined}
      />,
    );
    await user.click(screen.getByRole("button", { name: "Enregistrer les modifications" }));
    const button = await screen.findByRole("button", { name: "Résultat à vérifier" });
    expect(button.hasAttribute("disabled")).toBe(true);
    expect(onUpdate).toHaveBeenCalledOnce();
  });
});
