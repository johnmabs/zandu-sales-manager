import { cleanup, render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { afterEach, describe, expect, it, vi } from "vitest";

import { AdjustStockForm } from "../../apps/admin/src/features/inventory/stock-positions/AdjustStockForm";
import { ApiRequestError } from "../../packages/api-client/src/index";

afterEach(() => {
  cleanup();
  vi.restoreAllMocks();
});

describe("Stock adjustment form", () => {
  it("rejects a zero delta and requires a reason", async () => {
    const onAdjust = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(<AdjustStockForm currency="XAF" onAdjust={onAdjust} />);
    await user.type(screen.getByLabelText("Delta de quantité"), "0.000");
    await user.click(screen.getByRole("button", { name: "Ajuster le stock" }));
    expect(await screen.findByText("Le delta doit être différent de zéro.")).toBeTruthy();
    expect(screen.getByText("La raison est requise.")).toBeTruthy();
    expect(onAdjust).not.toHaveBeenCalled();
  });

  it("requires and preserves the exact unit cost for an incoming adjustment", async () => {
    const onAdjust = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(<AdjustStockForm currency="XAF" onAdjust={onAdjust} />);
    await user.type(screen.getByLabelText("Delta de quantité"), "001.250");
    await user.type(screen.getByLabelText("Raison de l’ajustement"), "Réassort manuel");
    await user.click(screen.getByRole("button", { name: "Ajuster le stock" }));
    expect(
      await screen.findByText("Le coût unitaire est requis pour une entrée de stock."),
    ).toBeTruthy();
    await user.type(screen.getByLabelText("Coût unitaire de l’entrée (XAF)"), "000800.125000");
    await user.click(screen.getByRole("button", { name: "Ajuster le stock" }));
    await waitFor(() =>
      expect(onAdjust).toHaveBeenCalledWith({
        delta: "001.250",
        reason: "Réassort manuel",
        unitCost: "000800.125000",
      }),
    );
  });

  it("omits unit cost for an outgoing adjustment", async () => {
    const onAdjust = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(<AdjustStockForm currency="XAF" onAdjust={onAdjust} />);
    await user.type(screen.getByLabelText("Delta de quantité"), "-000.125");
    expect(screen.queryByLabelText("Coût unitaire de l’entrée (XAF)")).toBeNull();
    await user.type(screen.getByLabelText("Raison de l’ajustement"), "Casse constatée");
    await user.click(screen.getByRole("button", { name: "Ajuster le stock" }));
    await waitFor(() =>
      expect(onAdjust).toHaveBeenCalledWith({
        delta: "-000.125",
        reason: "Casse constatée",
      }),
    );
  });

  it.each([
    [
      "STOCK_COUNT_PRODUCT_LOCKED",
      422,
      "Ce produit est verrouillé par un inventaire physique ouvert.",
    ],
    [
      "VALIDATION_ERROR",
      400,
      "L’ajustement est invalide ou la sortie dépasse le stock disponible.",
    ],
  ])(
    "shows the %s business failure without local stock arithmetic",
    async (code, status, message) => {
      const onAdjust = vi
        .fn()
        .mockRejectedValue(
          new ApiRequestError(
            { code, correlationId: "adjust-ref", kind: "response", status },
            false,
          ),
        );
      const user = userEvent.setup();
      render(<AdjustStockForm currency="XAF" onAdjust={onAdjust} />);
      await user.type(screen.getByLabelText("Delta de quantité"), "-2000");
      await user.type(screen.getByLabelText("Raison de l’ajustement"), "Perte");
      await user.click(screen.getByRole("button", { name: "Ajuster le stock" }));
      expect(await screen.findByText(message)).toBeTruthy();
      expect(screen.getByText("Référence de diagnostic : adjust-ref")).toBeTruthy();
    },
  );

  it("blocks blind replay when the POST result is unknown", async () => {
    const onAdjust = vi
      .fn()
      .mockRejectedValue(
        new ApiRequestError({ kind: "network", message: "The request timed out." }, true),
      );
    const user = userEvent.setup();
    render(<AdjustStockForm currency="XAF" onAdjust={onAdjust} />);
    await user.type(screen.getByLabelText("Delta de quantité"), "-1");
    await user.type(screen.getByLabelText("Raison de l’ajustement"), "Perte");
    await user.click(screen.getByRole("button", { name: "Ajuster le stock" }));
    const button = await screen.findByRole("button", { name: "Résultat à vérifier" });
    expect(button.hasAttribute("disabled")).toBe(true);
    await user.click(button);
    expect(onAdjust).toHaveBeenCalledTimes(1);
  });
});
