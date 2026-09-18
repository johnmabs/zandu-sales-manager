import { cleanup, render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { InitializeStockForm } from "../../apps/admin/src/features/inventory/stock-positions/InitializeStockForm";
import { ApiRequestError } from "../../packages/api-client/src/index";

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("Stock initialization form", () => {
  it("validates exact decimal syntax before confirmation", async () => {
    const onInitialize = vi.fn().mockResolvedValue(undefined);
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(true);
    const user = userEvent.setup();
    render(
      <InitializeStockForm currency="XAF" onInitialize={onInitialize} productLabel="Paracétamol" />,
    );
    await user.type(screen.getByLabelText("Quantité initiale"), "1e3");
    await user.type(screen.getByLabelText("Coût unitaire d’ouverture (XAF)"), "1,25");
    await user.click(screen.getByRole("button", { name: "Initialiser le stock" }));
    expect(await screen.findAllByText(/nombre décimal positif ou nul/)).toHaveLength(2);
    expect(confirm).not.toHaveBeenCalled();
    expect(onInitialize).not.toHaveBeenCalled();
  });

  it("preserves exact strings and requires explicit confirmation", async () => {
    const onInitialize = vi.fn().mockResolvedValue(undefined);
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(true);
    const user = userEvent.setup();
    render(
      <InitializeStockForm currency="XAF" onInitialize={onInitialize} productLabel="Paracétamol" />,
    );
    await user.type(screen.getByLabelText("Quantité initiale"), "001.250");
    await user.type(screen.getByLabelText("Coût unitaire d’ouverture (XAF)"), "000800.125000");
    await user.click(screen.getByRole("button", { name: "Initialiser le stock" }));
    await waitFor(() =>
      expect(onInitialize).toHaveBeenCalledWith({
        quantity: "001.250",
        unitCost: "000800.125000",
      }),
    );
    expect(confirm).toHaveBeenCalledWith(expect.stringContaining("Cette opération est unique"));
  });

  it("maps server field errors and preserves the correlation ID", async () => {
    const onInitialize = vi.fn().mockRejectedValue(
      new ApiRequestError(
        {
          correlationId: "initialize-ref",
          fieldErrors: { unitCost: ["Coût invalide côté serveur."] },
          kind: "response",
          status: 422,
        },
        false,
      ),
    );
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const user = userEvent.setup();
    render(
      <InitializeStockForm currency="XAF" onInitialize={onInitialize} productLabel="Paracétamol" />,
    );
    await user.type(screen.getByLabelText("Quantité initiale"), "1");
    await user.type(screen.getByLabelText("Coût unitaire d’ouverture (XAF)"), "800");
    await user.click(screen.getByRole("button", { name: "Initialiser le stock" }));
    expect(await screen.findByText("Coût invalide côté serveur.")).toBeTruthy();
    expect(screen.getByText("Référence de diagnostic : initialize-ref")).toBeTruthy();
  });

  it("blocks blind replay when the POST result is unknown", async () => {
    const onInitialize = vi
      .fn()
      .mockRejectedValue(
        new ApiRequestError({ kind: "network", message: "The request timed out." }, true),
      );
    vi.spyOn(window, "confirm").mockReturnValue(true);
    const user = userEvent.setup();
    render(
      <InitializeStockForm currency="XAF" onInitialize={onInitialize} productLabel="Paracétamol" />,
    );
    await user.type(screen.getByLabelText("Quantité initiale"), "1");
    await user.type(screen.getByLabelText("Coût unitaire d’ouverture (XAF)"), "800");
    await user.click(screen.getByRole("button", { name: "Initialiser le stock" }));
    const button = await screen.findByRole("button", { name: "Résultat à vérifier" });
    expect(button.hasAttribute("disabled")).toBe(true);
    await user.click(button);
    expect(onInitialize).toHaveBeenCalledTimes(1);
  });
});
