import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

import { breadcrumbsForPath } from "../src/shell/breadcrumbs.ts";
import { adminNavigation, visibleAdminNavigation } from "../src/shell/navigation.ts";

const shellUrl = new URL("../src/shell/AdminShell.tsx", import.meta.url);
const protectedShellUrl = new URL("../src/shell/ProtectedAdminShell.tsx", import.meta.url);
const errorBoundaryUrl = new URL("../app/app/error.tsx", import.meta.url);

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
