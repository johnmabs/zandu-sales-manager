import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

import { breadcrumbsForPath } from "../src/components/admin-shell/breadcrumbs.ts";
import {
  adminNavigation,
  visibleAdminNavigation,
} from "../src/components/admin-shell/navigation.ts";

const shellUrl = new URL("../src/components/admin-shell/AdminShell.tsx", import.meta.url);
const protectedShellUrl = new URL(
  "../src/components/admin-shell/ProtectedAdminShell.tsx",
  import.meta.url,
);
const errorBoundaryUrl = new URL("../app/app/error.tsx", import.meta.url);
const runtimeUrl = new URL("../src/runtime/AdminRuntime.tsx", import.meta.url);
const applicationShellUrl = new URL("../src/runtime/AdminApplicationShell.tsx", import.meta.url);

const access = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 1,
  organizationId: "organization-1",
  permissions: ["PRODUCT_READ", "SALE_READ"],
  scope: { type: "ORGANIZATION" },
};

test("Admin shell navigation is projected from effective capabilities", () => {
  assert.deepEqual(
    visibleAdminNavigation(access).map((item) => item.href),
    ["/app/catalog", "/app/sales"],
  );
  assert.deepEqual(breadcrumbsForPath("/app/catalog", adminNavigation), [
    { href: "/app", label: "Accueil" },
    { href: "/app/catalog", label: "Catalogue" },
  ]);
  assert.deepEqual(breadcrumbsForPath("/app/access/members", adminNavigation), [
    { href: "/app", label: "Accueil" },
    { href: "/app/access", label: "Accès" },
  ]);
});

test("Admin shell provides accessible desktop controls and protects unresolved sessions", async () => {
  const [shell, protectedShell, errorBoundary] = await Promise.all([
    readFile(shellUrl, "utf8"),
    readFile(protectedShellUrl, "utf8"),
    readFile(errorBoundaryUrl, "utf8"),
  ]);

  assert.match(shell, /aria-label="Navigation principale"/);
  assert.match(shell, /aria-label="Organisation active"/);
  assert.match(shell, /aria-label="Magasin actif"/);
  assert.match(shell, /aria-label="Fil d’Ariane"/);
  assert.match(shell, /NotificationViewport/);
  assert.match(shell, /Se déconnecter/);
  assert.match(shell, /Aller au contenu/);
  assert.match(protectedShell, /Chargement de la session/);
  assert.match(protectedShell, /authState.status === "UNAUTHENTICATED"/);
  assert.match(errorBoundary, /Réessayer/);
});

test("Admin runtime composes Symfony auth, tenant cache, contexts, and the protected shell", async () => {
  const [runtime, applicationShell] = await Promise.all([
    readFile(runtimeUrl, "utf8"),
    readFile(applicationShellUrl, "utf8"),
  ]);

  assert.match(runtime, /createAuthenticationTransport/);
  assert.match(runtime, /AuthenticationManager/);
  assert.match(runtime, /OrganizationContextManager/);
  assert.match(runtime, /StoreContextManager/);
  assert.match(runtime, /transitionOrganizationCache/);
  assert.match(applicationShell, /EffectiveAccessProvider/);
  assert.match(applicationShell, /ServerStateProvider/);
  assert.match(applicationShell, /<AdminShell/);
});

test("Admin runtime clears tenant state when authorization refresh cannot restore access", async () => {
  const runtime = await readFile(runtimeUrl, "utf8");
  assert.match(runtime, /revalidateSession/);
  assert.match(runtime, /services\.auth\.refresh\(\)/);
  assert.match(runtime, /services\.queryClient\.clear\(\)/);
  assert.match(runtime, /services\.organizations\.clear\(\)/);
});

test("member access mutations revalidate the current user's session", async () => {
  const memberDetails = await readFile(
    new URL("../src/features/access/members/MemberDetailsPage.tsx", import.meta.url),
    "utf8",
  );
  assert.match(memberDetails, /details\.data\?\.userId === authState\.actor\?\.userId/);
  assert.match(memberDetails, /await revalidateSession\(\)/);
});

test("member access mutations invalidate list and every versioned detail cache", async () => {
  const memberDetails = await readFile(
    new URL("../src/features/access/members/MemberDetailsPage.tsx", import.meta.url),
    "utf8",
  );
  assert.match(memberDetails, /queryKeys\.members\.list/);
  assert.match(memberDetails, /\["members", activeOrganizationId, membershipId\]/);
});
