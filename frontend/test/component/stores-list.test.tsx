import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { describe, expect, it, vi } from "vitest";

import { StoreCreateForm } from "../../apps/admin/src/features/stores/components/StoreCreateForm";
import {
  StoreDetails,
  storeDetailsErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreDetails";
import {
  StoreList,
  storeListErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreList";
import { ApiRequestError } from "../../packages/api-client/src/index";

const store = {
  address: null,
  code: "CENTRE",
  currency: "XAF",
  id: "store-1",
  locale: "fr_CG",
  name: "Centre-ville",
  organizationId: "organization-1",
  status: "ACTIVE" as const,
  timeZone: "Africa/Brazzaville",
};

describe("StoreList", () => {
  it("renders loading and the Store contract fields", () => {
    const { rerender } = render(<StoreList canCreate={false} isLoading stores={undefined} />);

    expect(screen.getByRole("status", { name: "Chargement des magasins" })).toBeTruthy();

    rerender(<StoreList canCreate={false} isLoading={false} stores={[store]} />);
    expect(screen.getByRole("table").textContent).toContain("Centre-ville");
    expect(screen.getByRole("table").textContent).toContain("CENTRE");
    expect(screen.getByRole("table").textContent).toContain("ACTIVE");
  });

  it("only proposes creation in the empty state when the client permission is known", () => {
    const { rerender } = render(<StoreList canCreate={false} isLoading={false} stores={[]} />);

    expect(screen.queryByRole("link", { name: "Créer un magasin" })).toBeNull();

    rerender(<StoreList canCreate isLoading={false} stores={[]} />);
    expect(screen.getByRole("link", { name: "Créer un magasin" }).getAttribute("href")).toBe(
      "/app/stores/new",
    );
  });

  it("distinguishes server denial, network failure, and other API failures", () => {
    expect(
      storeListErrorPresentation(new ApiRequestError({ kind: "response", status: 403 }, false))
        .title,
    ).toBe("Accès refusé");
    expect(storeListErrorPresentation(new ApiRequestError({ kind: "network" }, false)).title).toBe(
      "Connexion indisponible",
    );
    expect(
      storeListErrorPresentation(new ApiRequestError({ kind: "response", status: 422 }, false))
        .title,
    ).toBe("Impossible de charger les magasins");
  });
});

describe("StoreDetails", () => {
  it("renders the published profile, closure state, and status-permitted actions", () => {
    render(
      <StoreDetails
        actions={{ edit: true, reactivate: false, requestClosure: true, suspend: true }}
        isLoading={false}
        store={{ ...store, address: "12 avenue du Port" }}
      />,
    );

    expect(screen.getByRole("heading", { name: "Centre-ville" })).toBeTruthy();
    expect(screen.getByText("12 avenue du Port")).toBeTruthy();
    expect(screen.getByText("Aucune demande de fermeture en cours.")).toBeTruthy();
    expect(screen.getByText("Modifier")).toBeTruthy();
    expect(screen.getByText("Suspendre")).toBeTruthy();
    expect(screen.getByText("Demander la fermeture")).toBeTruthy();
    expect(screen.queryByText("Réactiver")).toBeNull();
  });

  it("masks stale and cross-tenant resources as not found while keeping a 403 distinct", () => {
    expect(
      storeDetailsErrorPresentation(new ApiRequestError({ kind: "response", status: 404 }, false))
        .title,
    ).toBe("Magasin introuvable");
    expect(
      storeDetailsErrorPresentation(new ApiRequestError({ kind: "response", status: 403 }, false))
        .title,
    ).toBe("Accès refusé");
  });
});

describe("StoreCreateForm", () => {
  it("submits only the published creation payload and normalizes an empty address to null", async () => {
    const user = userEvent.setup();
    const onCreate = vi.fn().mockResolvedValue(undefined);
    const view = render(
      <StoreCreateForm
        defaults={{ currency: "XAF", locale: "fr_CG", timeZone: "Africa/Brazzaville" }}
        onCreate={onCreate}
      />,
    );

    const form = within(view.container);
    await user.type(form.getByLabelText("Code"), "CENTRE");
    await user.type(form.getByLabelText("Nom"), "Centre-ville");
    await user.click(form.getByRole("button", { name: "Créer le magasin" }));

    await waitFor(() =>
      expect(onCreate).toHaveBeenCalledWith({
        address: null,
        code: "CENTRE",
        currency: "XAF",
        locale: "fr_CG",
        name: "Centre-ville",
        timeZone: "Africa/Brazzaville",
      }),
    );
  });

  it("preserves server validation errors on their corresponding fields", async () => {
    const user = userEvent.setup();
    const view = render(
      <StoreCreateForm
        defaults={{ currency: "XAF", locale: "fr_CG", timeZone: "Africa/Brazzaville" }}
        onCreate={async () => {
          throw new ApiRequestError(
            {
              code: "VALIDATION_ERROR",
              fieldErrors: { code: ["Le code doit être renseigné."] },
              kind: "response",
              status: 400,
            },
            false,
          );
        }}
      />,
    );

    const form = within(view.container);
    await user.type(form.getByLabelText("Code"), "CENTRE");
    await user.type(form.getByLabelText("Nom"), "Centre-ville");
    await user.click(form.getByRole("button", { name: "Créer le magasin" }));

    expect(await form.findByText("Le code doit être renseigné.")).toBeTruthy();
    expect(
      form.getByText("Certaines informations sont invalides. Corrigez les champs indiqués."),
    ).toBeTruthy();
  });

  it("maps a duplicate code conflict to the creation-specific message", async () => {
    const user = userEvent.setup();
    const view = render(
      <StoreCreateForm
        defaults={{ currency: "XAF", locale: "fr_CG", timeZone: "Africa/Brazzaville" }}
        onCreate={async () => {
          throw new ApiRequestError({ code: "CONFLICT", kind: "response", status: 409 }, false);
        }}
      />,
    );
    const form = within(view.container);

    await user.type(form.getByLabelText("Code"), "CENTRE");
    await user.type(form.getByLabelText("Nom"), "Centre-ville");
    await user.click(form.getByRole("button", { name: "Créer le magasin" }));

    expect(
      await form.findByText("Ce code de magasin est déjà utilisé dans l’organisation."),
    ).toBeTruthy();
  });
});
