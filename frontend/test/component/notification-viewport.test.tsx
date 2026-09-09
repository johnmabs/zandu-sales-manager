import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { describe, expect, it, vi } from "vitest";

import { NotificationCenter } from "../../packages/notifications/src/index";
import {
  ConfirmationDialog,
  NotificationProvider,
  NotificationViewport,
} from "../../packages/notifications/src/react";

describe("NotificationViewport", () => {
  it("renders and dismisses transient feedback through its accessible control", async () => {
    const user = userEvent.setup();
    const center = new NotificationCenter();
    center.notify({ message: "Produit ajouté", tone: "success" });

    render(
      <NotificationProvider notificationCenter={center}>
        <NotificationViewport />
      </NotificationProvider>,
    );

    expect(screen.getByRole("status").textContent).toContain("Produit ajouté");
    await user.click(screen.getByRole("button", { name: "Fermer la notification" }));
    expect(screen.queryByRole("status")).toBeNull();
  });

  it("requires an explicit confirmation for a consequential dialog action", async () => {
    const user = userEvent.setup();
    const confirm = vi.fn();

    render(
      <ConfirmationDialog
        confirmLabel="Supprimer"
        impact="Cette suppression est définitive."
        onCancel={vi.fn()}
        onConfirm={confirm}
        open
        title="Supprimer le produit"
      />,
    );

    expect(screen.getByRole("dialog", { name: "Supprimer le produit" }).textContent).toContain(
      "Cette suppression est définitive.",
    );
    await user.click(screen.getByRole("button", { name: "Supprimer" }));
    expect(confirm).toHaveBeenCalledOnce();
  });
});
