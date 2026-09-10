import { render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { describe, expect, it, vi } from "vitest";

import { storeContextMessageForStatus } from "../../apps/admin/src/components/admin-shell/AdminShell";
import { CancelStoreClosureDialog } from "../../apps/admin/src/features/stores/components/CancelStoreClosureDialog";
import { RequestStoreClosureDialog } from "../../apps/admin/src/features/stores/components/RequestStoreClosureDialog";
import { StoreCreateForm } from "../../apps/admin/src/features/stores/components/StoreCreateForm";
import {
  StoreDetails,
  storeDetailsErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreDetails";
import {
  StoreList,
  storeListErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreList";
import { StoreUpdateForm } from "../../apps/admin/src/features/stores/components/StoreUpdateForm";
import { SuspendStoreDialog } from "../../apps/admin/src/features/stores/components/SuspendStoreDialog";
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
  updatedAt: "2026-09-10T08:00:00+00:00",
  version: 1,
};

describe("Store context synchronization", () => {
  it("makes an unavailable active Store explicit after reconciliation", () => {
    expect(storeContextMessageForStatus("NO_SELECTABLE_STORES")).toContain(
      "Aucun magasin opérationnel",
    );
    expect(storeContextMessageForStatus("SELECTION_REQUIRED")).toContain("Sélectionnez");
    expect(storeContextMessageForStatus("ACTIVE")).toBeUndefined();
  });
});

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
        actions={{
          cancelClosure: false,
          edit: true,
          reactivate: false,
          requestClosure: true,
          suspend: true,
        }}
        isLoading={false}
        store={{ ...store, address: "12 avenue du Port" }}
      />,
    );

    expect(screen.getByRole("heading", { name: "Centre-ville" })).toBeTruthy();
    expect(screen.getByText("12 avenue du Port")).toBeTruthy();
    expect(screen.getByText("Aucune demande de fermeture en cours.")).toBeTruthy();
    expect(screen.getByRole("link", { name: "Modifier" }).getAttribute("href")).toBe(
      "/app/stores/store-1/edit",
    );
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

  it("presents the closure workflow response without treating it as a Store status patch", () => {
    render(
      <StoreDetails
        actions={{
          cancelClosure: true,
          edit: false,
          reactivate: false,
          requestClosure: false,
          suspend: false,
        }}
        closure={{
          blockers: ["OPEN_CASH_SESSION"],
          id: "closure-1",
          reason: "Fin d’activité",
          requestedAt: "2026-09-10T08:00:00+00:00",
          status: "IN_PROGRESS",
          storeId: "store-1",
          version: 1,
        }}
        isLoading={false}
        onCancelClosure={vi.fn()}
        store={{ ...store, status: "CLOSURE_PENDING" }}
      />,
    );

    expect(screen.getByText("Statut de la demande : IN_PROGRESS.")).toBeTruthy();
    expect(screen.getByText("Une session de caisse est encore ouverte.")).toBeTruthy();
    expect(screen.getByRole("button", { name: "Annuler la fermeture" })).toBeTruthy();
  });

  it("maps known closure blockers and keeps unknown codes safely diagnosable", () => {
    render(
      <StoreDetails
        actions={{
          cancelClosure: false,
          edit: false,
          reactivate: false,
          requestClosure: false,
          suspend: false,
        }}
        closure={{
          blockers: ["OPEN_STOCK_COUNT", "FUTURE_BLOCKER"],
          id: "closure-1",
          reason: "Fin d’activité",
          requestedAt: "2026-09-10T08:00:00+00:00",
          status: "IN_PROGRESS",
          storeId: "store-1",
          version: 1,
        }}
        isLoading={false}
        store={{ ...store, status: "CLOSURE_PENDING" }}
      />,
    );

    expect(screen.getByText("Un inventaire est encore ouvert.")).toBeTruthy();
    expect(
      screen.getByText(
        "Un élément opérationnel doit être résolu avant la fermeture (code diagnostic : FUTURE_BLOCKER).",
      ),
    ).toBeTruthy();
  });

  it("explains when the closure response contains no blocker", () => {
    render(
      <StoreDetails
        actions={{
          cancelClosure: false,
          edit: false,
          reactivate: false,
          requestClosure: false,
          suspend: false,
        }}
        closure={{
          blockers: [],
          id: "closure-1",
          reason: "Fin d’activité",
          requestedAt: "2026-09-10T08:00:00+00:00",
          status: "READY",
          storeId: "store-1",
          version: 1,
        }}
        isLoading={false}
        store={{ ...store, status: "CLOSURE_PENDING" }}
      />,
    );

    expect(screen.getByText("Aucun blocker n’a été signalé.")).toBeTruthy();
  });
});

describe("CancelStoreClosureDialog", () => {
  it("requires explicit confirmation and explains the resulting Store state", async () => {
    const user = userEvent.setup();
    const onConfirm = vi.fn();
    const view = render(
      <CancelStoreClosureDialog
        isCancelling={false}
        onClose={vi.fn()}
        onConfirm={onConfirm}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Annuler la fermeture du magasin" }),
    );

    expect(dialog.getByText(/le magasin redeviendra actif/)).toBeTruthy();
    await user.click(dialog.getByRole("button", { name: "Confirmer l’annulation" }));
    expect(onConfirm).toHaveBeenCalledOnce();
  });

  it("locks cancellation controls while the mutation is pending", () => {
    const view = render(
      <CancelStoreClosureDialog
        isCancelling
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Annuler la fermeture du magasin" }),
    );

    expect(dialog.getByRole("button", { name: "Retour" }).hasAttribute("disabled")).toBe(true);
    expect(
      dialog.getByRole("button", { name: "Annulation en cours" }).hasAttribute("disabled"),
    ).toBe(true);
  });

  it("keeps the action context coherent when the backend denies a visible action", () => {
    const view = render(
      <CancelStoreClosureDialog
        error={new ApiRequestError({ kind: "response", status: 403 }, false)}
        isCancelling={false}
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Annuler la fermeture du magasin" }),
    );

    expect(dialog.getByText("Vous n’êtes pas autorisé à effectuer cette action.")).toBeTruthy();
    expect(
      dialog.getByRole("button", { name: "Confirmer l’annulation" }).hasAttribute("disabled"),
    ).toBe(false);
  });
});

describe("RequestStoreClosureDialog", () => {
  it("requires a reason and sends the trimmed reason after confirmation", async () => {
    const user = userEvent.setup();
    const onConfirm = vi.fn();
    const view = render(
      <RequestStoreClosureDialog
        isRequesting={false}
        onClose={vi.fn()}
        onConfirm={onConfirm}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Demander la fermeture du magasin" }),
    );

    const confirm = dialog.getByRole("button", { name: "Confirmer la demande de fermeture" });
    expect(confirm.hasAttribute("disabled")).toBe(true);
    await user.type(dialog.getByLabelText("Motif de fermeture"), "  Fin d’activité  ");
    await user.click(confirm);

    expect(onConfirm).toHaveBeenCalledWith("Fin d’activité");
  });

  it("disables closure controls while the request is pending", () => {
    const view = render(
      <RequestStoreClosureDialog
        isRequesting
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Demander la fermeture du magasin" }),
    );

    expect(dialog.getByLabelText("Motif de fermeture").hasAttribute("disabled")).toBe(true);
    expect(dialog.getByRole("button", { name: "Annuler" }).hasAttribute("disabled")).toBe(true);
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

describe("StoreUpdateForm", () => {
  it("initializes from the server projection and submits only editable fields", async () => {
    const user = userEvent.setup();
    const onUpdate = vi.fn().mockResolvedValue(undefined);
    const view = render(
      <StoreUpdateForm
        onConflictReload={vi.fn()}
        onUpdate={onUpdate}
        store={{ ...store, address: null }}
      />,
    );
    const form = within(view.container);

    expect(form.getByText("Code : CENTRE")).toBeTruthy();
    expect(form.getByText("Devise : XAF")).toBeTruthy();
    expect(form.queryByLabelText("Code")).toBeNull();
    await user.clear(form.getByLabelText("Nom"));
    await user.type(form.getByLabelText("Nom"), "Centre rénové");
    await user.type(form.getByLabelText("Adresse"), "15 avenue du Port");
    await user.click(form.getByRole("button", { name: "Enregistrer les modifications" }));

    await waitFor(() =>
      expect(onUpdate).toHaveBeenCalledWith({
        address: "15 avenue du Port",
        locale: "fr_CG",
        name: "Centre rénové",
        timeZone: "Africa/Brazzaville",
      }),
    );
  });

  it("does not silently overwrite a concurrent server update", async () => {
    const user = userEvent.setup();
    const onConflictReload = vi.fn();
    const view = render(
      <StoreUpdateForm
        onConflictReload={onConflictReload}
        onUpdate={async () => {
          throw new ApiRequestError({ code: "CONFLICT", kind: "response", status: 409 }, false);
        }}
        store={store}
      />,
    );
    const form = within(view.container);

    await user.clear(form.getByLabelText("Nom"));
    await user.type(form.getByLabelText("Nom"), "Centre rénové");
    await user.click(form.getByRole("button", { name: "Enregistrer les modifications" }));

    expect(
      await form.findByText(
        "Ce magasin a été modifié entre-temps. Rechargez les données avant de réessayer.",
      ),
    ).toBeTruthy();
    await user.click(form.getByRole("button", { name: "Recharger les données" }));
    expect(onConflictReload).toHaveBeenCalledOnce();
  });
});

describe("SuspendStoreDialog", () => {
  it("requires confirmation and explains the operational consequence", async () => {
    const user = userEvent.setup();
    const onConfirm = vi.fn();
    const view = render(
      <SuspendStoreDialog
        isSuspending={false}
        onClose={vi.fn()}
        onConfirm={onConfirm}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Suspendre le magasin" }),
    );

    expect(dialog.getByText(/bloquera les nouvelles opérations/)).toBeTruthy();
    await user.click(dialog.getByRole("button", { name: "Confirmer la suspension" }));
    expect(onConfirm).toHaveBeenCalledOnce();
  });

  it("disables confirmation controls while the suspension is pending", () => {
    const view = render(
      <SuspendStoreDialog
        isSuspending
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Suspendre le magasin" }),
    );

    expect(dialog.getByRole("button", { name: "Annuler" }).hasAttribute("disabled")).toBe(true);
    expect(
      dialog.getByRole("button", { name: "Suspension en cours" }).hasAttribute("disabled"),
    ).toBe(true);
  });
});
