import { fireEvent, render, screen, waitFor, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import React from "react";
import { describe, expect, it, vi } from "vitest";

import { storeContextMessageForStatus } from "../../apps/admin/src/components/admin-shell/AdminShell";
import { InvitationLifecycleUnavailable } from "../../apps/admin/src/features/access/invitations/InvitationLifecycleUnavailable";
import { InvitationSuccessState } from "../../apps/admin/src/features/access/invitations/InvitationSuccessState";
import { InviteMemberForm } from "../../apps/admin/src/features/access/invitations/InviteMemberForm";
import { AssignRoleDialog } from "../../apps/admin/src/features/access/members/AssignRoleDialog";
import { MemberDetails } from "../../apps/admin/src/features/access/members/MemberDetails";
import { MemberList } from "../../apps/admin/src/features/access/members/MemberList";
import { RemoveRoleDialog } from "../../apps/admin/src/features/access/members/RemoveRoleDialog";
import { SuspendMemberDialog } from "../../apps/admin/src/features/access/members/SuspendMemberDialog";
import { RoleCatalog } from "../../apps/admin/src/features/access/roles/RoleCatalog";
import { CancelStoreClosureDialog } from "../../apps/admin/src/features/stores/components/CancelStoreClosureDialog";
import { RequestStoreClosureDialog } from "../../apps/admin/src/features/stores/components/RequestStoreClosureDialog";
import { StoreCreateForm } from "../../apps/admin/src/features/stores/components/StoreCreateForm";
import {
  StoreDetails,
  storeDetailsErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreDetails";
import { StoreErrorState } from "../../apps/admin/src/features/stores/components/StoreErrorState";
import {
  StoreList,
  storeListErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreList";
import { StoreUpdateForm } from "../../apps/admin/src/features/stores/components/StoreUpdateForm";
import { SuspendStoreDialog } from "../../apps/admin/src/features/stores/components/SuspendStoreDialog";
import {
  SingleFlight,
  hasUnknownStoreMutationOutcome,
} from "../../apps/admin/src/features/stores/mutationSafety";
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

const member = {
  authorizationVersion: 2,
  createdAt: "2026-09-10T08:00:00+00:00",
  id: "membership-1",
  organizationId: "organization-1",
  roleAssignments: [
    {
      assignmentId: "assignment-1",
      expiresAt: null,
      roleId: "STORE_MANAGER",
      scopeType: "SELECTED_STORES",
      storeIds: ["store-1", "store-2"],
    },
  ],
  status: "ACTIVE" as const,
  updatedAt: "2026-09-10T08:00:00+00:00",
  userId: "user-1",
  version: 1,
};

const role = {
  code: "STORE_MANAGER",
  description: "Gère les opérations d’un magasin.",
  id: "role-1",
  name: "Responsable de magasin",
  permissions: ["STORE_READ", "INVENTORY_READ"],
  status: "ACTIVE" as const,
  type: "SYSTEM" as const,
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

describe("Store mutation safety", () => {
  it("allows only one command while the same user intention is pending", async () => {
    const singleFlight = new SingleFlight();
    let release: (() => void) | undefined;
    const first = singleFlight.run(
      () =>
        new Promise<string>((resolve) => {
          release = () => resolve("done");
        }),
    );
    const second = await singleFlight.run(async () => "must-not-run");

    expect(second).toBeUndefined();
    release?.();
    await expect(first).resolves.toBe("done");
  });

  it("keeps a timeout outcome ambiguous instead of treating it as a confirmed failure", () => {
    expect(
      hasUnknownStoreMutationOutcome(
        new ApiRequestError({ kind: "network", message: "The request timed out." }, false),
      ),
    ).toBe(true);
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
    const { rerender } = render(
      <StoreList canCreate={false} error={null} isLoading={false} stores={[]} />,
    );

    expect(screen.queryByRole("link", { name: "Créer un magasin" })).toBeNull();
    expect(screen.queryByText("Erreur du service")).toBeNull();

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
    ).toBe("Action impossible");
  });

  it("keeps a safe diagnostic reference and a retry action for retryable failures", () => {
    const onRetry = vi.fn();
    render(
      <StoreErrorState
        error={
          new ApiRequestError(
            { correlationId: "0198c728-8f2d-7f43-92d8-3f0c75b80186", kind: "network" },
            false,
          )
        }
        onRetry={onRetry}
        scope="list"
      />,
    );

    expect(screen.getByRole("alert").textContent).toContain("Connexion indisponible");
    expect(screen.getByText(/Référence de diagnostic/).textContent).toContain(
      "0198c728-8f2d-7f43-92d8-3f0c75b80186",
    );
    screen.getByRole("button", { name: "Réessayer" }).click();
    expect(onRetry).toHaveBeenCalledOnce();
  });
});

describe("MemberList", () => {
  it("renders only the published member identity, status, role IDs, and scope summary", () => {
    const { container, rerender } = render(<MemberList isLoading members={undefined} />);

    expect(screen.getByRole("status", { name: "Chargement des membres" })).toBeTruthy();

    rerender(<MemberList isLoading={false} members={[member]} />);
    const table = within(container).getByRole("table");
    expect(table.textContent).toContain("user-1");
    expect(table.textContent).toContain("ACTIVE");
    expect(table.textContent).toContain("STORE_MANAGER");
    expect(table.textContent).toContain("2 magasins");
  });

  it("does not add local search or filters when the member API does not expose them", () => {
    render(<MemberList isLoading={false} members={[]} />);

    expect(screen.getByText("Aucun membre")).toBeTruthy();
    expect(screen.queryByRole("searchbox")).toBeNull();
  });
});

describe("MemberDetails", () => {
  it("renders the tenant-scoped membership projection without account credentials", () => {
    render(
      <MemberDetails
        isLoading={false}
        member={{
          ...member,
          roleAssignments: [
            {
              assignmentId: "assignment-organization",
              expiresAt: "2026-12-31T00:00:00+00:00",
              roleId: "ORGANIZATION_OWNER",
              scopeType: "ORGANIZATION",
              storeIds: [],
            },
            member.roleAssignments[0],
          ],
        }}
      />,
    );

    expect(screen.getByRole("heading", { name: "Membre" })).toBeTruthy();
    expect(screen.getByText("Identifiant utilisateur : user-1")).toBeTruthy();
    expect(screen.getByText("Statut : ACTIVE")).toBeTruthy();
    expect(screen.getByText("Rôle : ORGANIZATION_OWNER")).toBeTruthy();
    expect(screen.getByText("Portée : Organisation entière")).toBeTruthy();
    expect(screen.getByText("store-1")).toBeTruthy();
    expect(screen.getByText("store-2")).toBeTruthy();
    expect(screen.queryByText(/mot de passe|jeton|token/i)).toBeNull();
  });

  it("keeps a missing or cross-tenant member indistinguishable from not found", () => {
    const { container } = render(
      <MemberDetails
        error={new ApiRequestError({ kind: "response", status: 404 }, false)}
        isLoading={false}
      />,
    );

    expect(within(container).getByRole("alert").textContent).toContain("Membre introuvable");
  });
});

describe("AssignRoleDialog", () => {
  it("submits the selected role with an unambiguous organization scope", async () => {
    const onConfirm = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    render(
      <AssignRoleDialog
        isAssigning={false}
        onClose={vi.fn()}
        onConfirm={onConfirm}
        open
        roles={[role]}
        stores={[store]}
      />,
    );
    await user.selectOptions(screen.getByLabelText("Rôle"), "role-1");
    await user.click(screen.getByRole("button", { name: "Attribuer le rôle" }));
    await waitFor(() =>
      expect(onConfirm).toHaveBeenCalledWith({
        expiresAt: null,
        roleId: "role-1",
        scopeType: "ORGANIZATION",
        storeIds: [],
      }),
    );
  });

  it("requires reinforced confirmation for the organization owner role", async () => {
    const user = userEvent.setup();
    const { container } = render(
      <AssignRoleDialog
        isAssigning={false}
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
        roles={[{ ...role, code: "ORGANIZATION_OWNER", id: "owner-role", name: "Propriétaire" }]}
        stores={[store]}
      />,
    );
    await user.selectOptions(within(container).getByLabelText("Rôle"), "owner-role");
    expect(
      within(container).getByRole("button", { name: "Attribuer le rôle" }).hasAttribute("disabled"),
    ).toBe(true);
    expect(within(container).queryByLabelText("Magasins sélectionnés")).toBeNull();
    await user.click(within(container).getByLabelText(/Je confirme l’attribution/));
    expect(
      within(container).getByRole("button", { name: "Attribuer le rôle" }).hasAttribute("disabled"),
    ).toBe(false);
  });
});

describe("RemoveRoleDialog", () => {
  it("names the role, scope and affected stores before removal", async () => {
    const onConfirm = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    const { container } = render(
      <RemoveRoleDialog
        assignment={member.roleAssignments[0]}
        isRemoving={false}
        onClose={vi.fn()}
        onConfirm={onConfirm}
        open
      />,
    );
    expect(within(container).getByRole("dialog").textContent).toContain("STORE_MANAGER");
    expect(within(container).getByRole("dialog").textContent).toContain("store-1, store-2");
    await user.click(within(container).getByRole("button", { name: "Confirmer le retrait" }));
    expect(onConfirm).toHaveBeenCalledOnce();
  });
});

describe("SuspendMemberDialog", () => {
  it("names the member, immediate consequence, and reversibility", () => {
    const { container } = render(
      <SuspendMemberDialog
        isPending={false}
        memberLabel="user-1"
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
      />,
    );
    expect(within(container).getByRole("dialog").textContent).toContain("user-1");
    expect(within(container).getByRole("dialog").textContent).toContain("immédiatement");
    expect(within(container).getByRole("dialog").textContent).toContain("réversible");
  });
});

describe("RoleCatalog", () => {
  it("renders published role fields and marks system roles as read-only", () => {
    const { container, rerender } = render(<RoleCatalog isLoading roles={undefined} />);

    expect(within(container).getByRole("status", { name: "Chargement des rôles" })).toBeTruthy();

    rerender(<RoleCatalog isLoading={false} roles={[role]} />);
    const table = within(container).getByRole("table");
    expect(table.textContent).toContain("Responsable de magasin");
    expect(table.textContent).toContain("STORE_MANAGER");
    expect(table.textContent).toContain("Gère les opérations d’un magasin.");
    expect(table.textContent).toContain("Consulter le stock");
    expect(table.textContent).toContain("ACTIVE");
    expect(table.textContent).toContain("Système (lecture seule)");
    expect(within(container).queryByRole("button", { name: /modifier|supprimer/i })).toBeNull();
  });
});

describe("InviteMemberForm", () => {
  it("collects one or more catalog roles as organization-scoped invitation intentions", async () => {
    const onInvite = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    const { container } = render(
      <InviteMemberForm
        onInvite={onInvite}
        roles={[
          role,
          {
            ...role,
            code: "CASHIER",
            id: "role-2",
            name: "Caissier",
          },
        ]}
        storeScope={{ isLoading: false, stores: [store] }}
      />,
    );

    await user.type(within(container).getByLabelText("Email"), "member@zandu.test");
    await user.click(within(container).getByLabelText("Responsable de magasin (STORE_MANAGER)"));
    await user.click(within(container).getByLabelText("Caissier (CASHIER)"));
    await user.click(within(container).getByRole("button", { name: "Envoyer l’invitation" }));

    await waitFor(() =>
      expect(onInvite).toHaveBeenCalledWith({
        email: "member@zandu.test",
        expiresAt: null,
        roleAssignments: [
          { roleCode: "STORE_MANAGER", storeIds: [] },
          { roleCode: "CASHIER", storeIds: [] },
        ],
      }),
    );
  });

  it("builds selected-store invitation intentions without treating them as permissions", async () => {
    const onInvite = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    const { container } = render(
      <InviteMemberForm
        onInvite={onInvite}
        roles={[role]}
        storeScope={{ isLoading: false, stores: [store] }}
      />,
    );

    await user.type(within(container).getByLabelText("Email"), "manager@zandu.test");
    await user.click(within(container).getByLabelText("Responsable de magasin (STORE_MANAGER)"));
    await user.click(within(container).getByLabelText("Magasins sélectionnés"));
    await user.click(within(container).getByLabelText("Centre-ville (CENTRE)"));
    await user.click(within(container).getByRole("button", { name: "Envoyer l’invitation" }));

    await waitFor(() =>
      expect(onInvite).toHaveBeenCalledWith({
        email: "manager@zandu.test",
        expiresAt: null,
        roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: ["store-1"] }],
      }),
    );
    expect(within(container).getByText(/n’accorde aucun droit/)).toBeTruthy();
  });
});

describe("InvitationSuccessState", () => {
  it("shows only the returned invitation data and copies the one-time secret on request", async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    const { container } = render(
      <InvitationSuccessState
        copySecret={writeText}
        invitation={{
          invitation: {
            acceptedAt: null,
            email: "member@zandu.test",
            expiresAt: "2026-10-01T08:00:00+00:00",
            id: "invitation-1",
            organizationId: "organization-1",
            roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: ["store-1"] }],
            status: "PENDING",
            version: 1,
          },
          token: "one-time-invitation-secret",
        }}
      />,
    );

    expect(within(container).getByRole("heading", { name: "Invitation créée" })).toBeTruthy();
    expect(
      within(container).getByText(
        (_, element) => element?.textContent === "L’invitation pour member@zandu.test a été créée.",
      ),
    ).toBeTruthy();
    expect(within(container).getByText("PENDING")).toBeTruthy();
    expect(within(container).getByText(/ne pourra peut-être pas être récupéré/)).toBeTruthy();
    await user.click(within(container).getByRole("button", { name: "Copier le secret" }));

    await waitFor(() => expect(writeText).toHaveBeenCalledWith("one-time-invitation-secret"));
    expect(within(container).getByRole("status").textContent).toContain("Secret copié");
  });

  it("confirms the invitation target and consequence before cancellation", async () => {
    const onCancel = vi.fn().mockResolvedValue(undefined);
    const user = userEvent.setup();
    const invitation = {
      invitation: {
        acceptedAt: null,
        email: "member@zandu.test",
        expiresAt: "2026-10-01T08:00:00+00:00",
        id: "invitation-1",
        organizationId: "organization-1",
        roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: [] }],
        status: "PENDING",
        version: 1,
      },
      token: "secret",
    };

    const { container } = render(
      <InvitationSuccessState canCancel invitation={invitation} onCancel={onCancel} />,
    );
    await user.click(within(container).getByRole("button", { name: "Annuler l’invitation" }));
    expect(within(container).getByRole("dialog").textContent).toContain("member@zandu.test");
    expect(within(container).getByRole("dialog").textContent).toContain("empêchera définitivement");
    await user.click(within(container).getByRole("button", { name: "Confirmer l’annulation" }));

    await waitFor(() => expect(onCancel).toHaveBeenCalledOnce());
    expect(within(container).getByText("Invitation annulée.")).toBeTruthy();
  });
});

describe("InvitationLifecycleUnavailable", () => {
  it("does not invent a list or lifecycle actions when the API exposes no invitation read operation", () => {
    const { container } = render(<InvitationLifecycleUnavailable />);

    expect(
      within(container).getByRole("heading", { name: "Suivi des invitations indisponible" }),
    ).toBeTruthy();
    expect(within(container).getByRole("status").textContent).toContain("ne permet pas");
    expect(
      within(container).getByRole("link", { name: "Inviter un membre" }).getAttribute("href"),
    ).toBe("/app/access/invite");
    expect(within(container).queryByRole("table")).toBeNull();
    expect(
      within(container).queryByRole("button", { name: /annuler|renvoyer|prolonger|modifier/i }),
    ).toBeNull();
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
        error={null}
        isLoading={false}
        store={{ ...store, address: "12 avenue du Port" }}
      />,
    );

    expect(screen.getByRole("heading", { name: "Centre-ville" })).toBeTruthy();
    expect(screen.getByText("12 avenue du Port")).toBeTruthy();
    expect(screen.getByText("Aucune demande de fermeture en cours.")).toBeTruthy();
    expect(screen.queryByText("Erreur du service")).toBeNull();
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

  it("announces the transition consequence and supports closing with the keyboard", () => {
    const onClose = vi.fn();
    const view = render(
      <RequestStoreClosureDialog
        isRequesting={false}
        onClose={onClose}
        onConfirm={vi.fn()}
        open
        storeName="Centre-ville"
      />,
    );
    const dialogElement = within(view.container).getByRole("dialog", {
      name: "Demander la fermeture du magasin",
    });
    const dialog = within(dialogElement);
    const descriptionId = dialogElement.getAttribute("aria-describedby");

    expect(descriptionId).not.toBeNull();
    expect(document.getElementById(descriptionId ?? "")?.textContent).toContain("Centre-ville");
    expect(dialog.getByRole("button", { name: "Fermer le dialogue" })).toBeTruthy();
    fireEvent.keyDown(document, { key: "Escape" });
    expect(onClose).toHaveBeenCalledOnce();
  });

  it("keeps the closure intention visible but blocks a blind retry after timeout", () => {
    const view = render(
      <RequestStoreClosureDialog
        error={
          new ApiRequestError(
            {
              correlationId: "0198c728-8f2d-7f43-92d8-3f0c75b80186",
              kind: "network",
              message: "The request timed out.",
            },
            false,
          )
        }
        isRequesting={false}
        onClose={vi.fn()}
        onConfirm={vi.fn()}
        open
        storeName="Centre-ville"
      />,
    );
    const dialog = within(
      within(view.container).getByRole("dialog", { name: "Demander la fermeture du magasin" }),
    );

    expect(dialog.getByText(/a peut-être été enregistrée/)).toBeTruthy();
    expect(dialog.getByText(/Référence de diagnostic/).textContent).toContain(
      "0198c728-8f2d-7f43-92d8-3f0c75b80186",
    );
    expect(
      dialog.getByRole("button", { name: "Résultat à vérifier" }).hasAttribute("disabled"),
    ).toBe(true);
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
              correlationId: "0198c728-8f2d-7f43-92d8-3f0c75b80186",
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
    const code = form.getByLabelText("Code");
    const codeErrorId = code.getAttribute("aria-describedby");
    expect(codeErrorId).not.toBeNull();
    expect(document.getElementById(codeErrorId ?? "")?.textContent).toContain(
      "Le code doit être renseigné.",
    );
    expect(
      form.getByText("Certaines informations sont invalides. Corrigez les champs indiqués."),
    ).toBeTruthy();
    expect(form.getByText(/Référence de diagnostic/).textContent).toContain(
      "0198c728-8f2d-7f43-92d8-3f0c75b80186",
    );
  });

  it("preserves the creation intent and prevents a blind retry after timeout", async () => {
    const user = userEvent.setup();
    const view = render(
      <StoreCreateForm
        defaults={{ currency: "XAF", locale: "fr_CG", timeZone: "Africa/Brazzaville" }}
        onCreate={async () => {
          throw new ApiRequestError({ kind: "network", message: "The request timed out." }, false);
        }}
      />,
    );
    const form = within(view.container);
    await user.type(form.getByLabelText("Code"), "CENTRE");
    await user.type(form.getByLabelText("Nom"), "Centre-ville");
    await user.click(form.getByRole("button", { name: "Créer le magasin" }));

    expect(await form.findByText(/La création a peut-être été enregistrée/)).toBeTruthy();
    expect(form.getByDisplayValue("CENTRE")).toBeTruthy();
    expect(form.getByRole("button", { name: "Résultat à vérifier" }).hasAttribute("disabled")).toBe(
      true,
    );
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
