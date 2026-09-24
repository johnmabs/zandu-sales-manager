import { expect, test } from "@playwright/test";

import type { MembershipRoleAssignment } from "../../packages/api-client/src/index";
import type { Page, Route } from "@playwright/test";

const organizationId = "organization-1";
const permissions = [
  "MEMBER_READ",
  "MEMBER_INVITE",
  "MEMBER_SUSPEND",
  "MEMBER_REVOKE",
  "ROLE_READ",
  "ROLE_ASSIGN",
  "ROLE_REVOKE",
  "STORE_READ",
];

test("an owner administers invitation, roles and membership lifecycle", async ({ page }) => {
  let member = memberResource("ACTIVE", []);
  let invitation = invitationResource();
  await mockAccessApi(
    page,
    async (path, method, route) => {
      if (path === "/api/member-invitations" && method === "POST") {
        await route.fulfill({
          json: { invitation: invitationResource(), token: "one-time-secret" },
          status: 201,
        });
        return true;
      }
      if (path === "/api/member-invitations" && method === "GET") {
        await route.fulfill({ json: [invitation] });
        return true;
      }
      if (path === "/api/member-invitations/invitation-1/cancel" && method === "POST") {
        invitation = { ...invitation, status: "CANCELLED", version: 2 };
        await route.fulfill({ json: invitation });
        return true;
      }
      if (path === "/api/members/membership-2/role-assignments" && method === "POST") {
        member = memberResource("ACTIVE", [
          {
            assignmentId: "assignment-1",
            expiresAt: null,
            roleId: "role-manager",
            scopeType: "ORGANIZATION",
            storeIds: [],
          },
        ]);
        await route.fulfill({ json: member, status: 201 });
        return true;
      }
      if (path.endsWith("/role-assignments/assignment-1") && method === "DELETE") {
        member = memberResource("ACTIVE", []);
        await route.fulfill({ json: member });
        return true;
      }
      if (path.endsWith("/suspend")) {
        member = memberResource("SUSPENDED", member.roleAssignments);
        await route.fulfill({ json: member });
        return true;
      }
      if (path.endsWith("/reactivate")) {
        member = memberResource("ACTIVE", member.roleAssignments);
        await route.fulfill({ json: member });
        return true;
      }
      return false;
    },
    () => member,
  );

  await login(page);
  await openAccess(page);
  await page.getByRole("link", { name: "Invitations" }).click();
  await expect(page.getByText("member@zandu.test")).toBeVisible();
  await page.getByRole("button", { name: "Annuler l’invitation de member@zandu.test" }).click();
  await page.getByRole("button", { name: "Confirmer l’annulation" }).click();
  await expect(page.getByText("Invitation annulée.")).toBeVisible();
  await expect(page.getByText("CANCELLED", { exact: true })).toBeVisible();
  await page.getByRole("link", { name: "Inviter un membre" }).click();
  await page.getByLabel("Email").fill("member@zandu.test");
  await page.getByLabel("Responsable (STORE_MANAGER)").check();
  await page.getByRole("button", { name: "Envoyer l’invitation" }).click();
  await expect(page.getByRole("heading", { name: "Invitation créée" })).toBeVisible();

  await openMember(page);
  await page.getByRole("button", { name: "Attribuer un rôle" }).click();
  const assignRoleDialog = page.getByRole("dialog", { name: "Attribuer un rôle", exact: true });
  await assignRoleDialog
    .getByRole("combobox", { name: "Rôle", exact: true })
    .selectOption("role-manager");
  await assignRoleDialog.getByRole("button", { name: "Attribuer le rôle", exact: true }).click();
  await expect(assignRoleDialog).toBeHidden();
  await expect(page.getByText("role-manager", { exact: true }).first()).toBeVisible();
  await page.getByRole("button", { name: "Retirer ce rôle" }).click();
  await page.getByRole("button", { name: "Confirmer le retrait" }).click();
  await page.getByRole("button", { name: "Suspendre le membre" }).click();
  await page.getByRole("button", { name: "Confirmer la suspension" }).click();
  await expect(page.getByText("SUSPENDED", { exact: true })).toBeVisible();
  await page.getByRole("button", { name: "Réactiver le membre" }).click();
  await page.getByRole("button", { name: "Confirmer la réactivation" }).click();
  await expect(page.getByText("ACTIVE", { exact: true })).toBeVisible();
});

test("last owner refusal keeps the member UI coherent", async ({ page }) => {
  const ownerAssignment = {
    assignmentId: "owner-assignment",
    expiresAt: null,
    roleId: "role-owner",
    scopeType: "ORGANIZATION",
    storeIds: [],
  };
  const member = memberResource("ACTIVE", [ownerAssignment]);
  await mockAccessApi(
    page,
    async (path, method, route) => {
      if (path.endsWith("/role-assignments/owner-assignment") && method === "DELETE") {
        await route.fulfill({
          json: {
            code: "DOMAIN_RULE_VIOLATION",
            correlationId: "owner-refusal",
            message: "The last active organization owner cannot be suspended or revoked.",
          },
          status: 409,
        });
        return true;
      }
      return false;
    },
    () => member,
  );
  await login(page);
  await openMember(page);
  await page.getByRole("button", { name: "Retirer ce rôle" }).click();
  await page.getByRole("button", { name: "Confirmer le retrait" }).click();
  await expect(
    page.getByText("L’organisation doit conserver au moins un propriétaire actif."),
  ).toBeVisible();
  await expect(page.getByText("role-owner", { exact: true }).first()).toBeVisible();
});

async function login(page: Page) {
  await page.goto("/login");
  await page.getByLabel("Adresse e-mail").fill("owner@zandu.test");
  await page.getByLabel("Mot de passe").fill("password");
  await page.getByRole("button", { name: "Se connecter" }).click();
  await expect(page).toHaveURL(/\/admin$/);
}

async function openAccess(page: Page) {
  await page
    .getByRole("navigation", { name: "Navigation principale" })
    .getByRole("link", { name: "Accès" })
    .click();
}

async function openMember(page: Page) {
  await openAccess(page);
  await page.getByRole("link", { name: "Membres" }).click();
  await page.getByRole("link", { name: "member-user" }).click();
}

async function mockAccessApi(
  page: Page,
  mutate: (path: string, method: string, route: Route) => Promise<boolean>,
  currentMember: () => ReturnType<typeof memberResource>,
) {
  await page.route("**/api/**", async (route) => {
    const request = route.request();
    const path = new URL(request.url()).pathname;
    if (path === "/api/auth/refresh") return route.fulfill({ json: {}, status: 401 });
    if (path === "/api/auth/login") return route.fulfill({ json: { token: token() } });
    if (path === "/api/session") return route.fulfill({ json: session() });
    if (path === "/api/stores") return route.fulfill({ json: [store()] });
    if (path === "/api/roles") return route.fulfill({ json: roles() });
    if (path === "/api/members") return route.fulfill({ json: [currentMember()] });
    if (path === "/api/members/membership-2" && request.method() === "GET")
      return route.fulfill({ json: currentMember() });
    if (await mutate(path, request.method(), route)) return;
    await route.fulfill({ json: {}, status: 404 });
  });
}

function session() {
  return {
    authorizationVersion: 1,
    effectiveAccess: {
      accessibleStoreIds: ["store-1"],
      authorizationVersion: 1,
      organizationId,
      permissions,
      scope: { type: "ORGANIZATION" },
    },
    email: "owner@zandu.test",
    id: "actor-1",
    organizationId,
    organizations: [
      {
        defaultCurrency: "XAF",
        defaultLocale: "fr_CG",
        defaultTimeZone: "Africa/Lagos",
        id: organizationId,
        name: "Zandu",
        status: "ACTIVE",
      },
    ],
    userId: "owner-user",
  };
}
function token() {
  return `header.${Buffer.from(JSON.stringify({ authorizationVersion: 1, exp: Math.floor(Date.now() / 1000) + 60 })).toString("base64url")}.signature`;
}
function store() {
  return {
    address: null,
    code: "CENTRE",
    currency: "XAF",
    id: "store-1",
    locale: "fr_CG",
    name: "Centre",
    organizationId,
    status: "ACTIVE",
    timeZone: "Africa/Lagos",
    updatedAt: "2026-09-13T08:00:00Z",
    version: 1,
  };
}
function roles() {
  return [
    {
      code: "STORE_MANAGER",
      description: null,
      id: "role-manager",
      name: "Responsable",
      permissions: [],
      status: "ACTIVE",
      type: "SYSTEM",
      version: 1,
    },
    {
      code: "ORGANIZATION_OWNER",
      description: null,
      id: "role-owner",
      name: "Propriétaire",
      permissions,
      status: "ACTIVE",
      type: "SYSTEM",
      version: 1,
    },
  ];
}
function invitationResource() {
  return {
    acceptedAt: null,
    email: "member@zandu.test",
    expiresAt: "2026-10-01T08:00:00Z",
    id: "invitation-1",
    organizationId,
    roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: [] }],
    status: "PENDING",
    version: 1,
  };
}
function memberResource(
  status: "ACTIVE" | "SUSPENDED",
  roleAssignments: readonly MembershipRoleAssignment[],
) {
  return {
    authorizationVersion: 1,
    createdAt: "2026-09-13T08:00:00Z",
    id: "membership-2",
    organizationId,
    roleAssignments,
    status,
    updatedAt: "2026-09-13T08:00:00Z",
    userId: "member-user",
    version: 1,
  };
}
