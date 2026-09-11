import { expect, test } from "@playwright/test";

const organizationId = "organization-1";

test("an authorized user completes the Stores vertical slice", async ({ page }) => {
  let store = storeResource("ACTIVE", "Centre-ville", 1);
  let created = false;

  await page.route("**/api/**", async (route) => {
    const request = route.request();
    const path = new URL(request.url()).pathname;

    if (path === "/api/auth/refresh") {
      await route.fulfill({ json: { code: "UNAUTHENTICATED" }, status: 401 });
      return;
    }
    if (path === "/api/auth/login") {
      await route.fulfill({ json: { token: token() } });
      return;
    }
    if (path === "/api/session") {
      await route.fulfill({
        json: session([
          "STORE_READ",
          "STORE_CREATE",
          "STORE_UPDATE",
          "STORE_SUSPEND",
          "STORE_CLOSE",
        ]),
      });
      return;
    }
    if (path === "/api/stores" && request.method() === "GET") {
      await route.fulfill({ json: created ? [store] : [] });
      return;
    }
    if (path === "/api/stores" && request.method() === "POST") {
      created = true;
      store = storeResource("ACTIVE", "Nouveau magasin", 1);
      await route.fulfill({ json: store, status: 201 });
      return;
    }
    if (path === "/api/stores/store-1" && request.method() === "GET") {
      await route.fulfill({ json: store });
      return;
    }
    if (path === "/api/stores/store-1" && request.method() === "PATCH") {
      store = storeResource("ACTIVE", "Magasin rénové", 2);
      await route.fulfill({ json: store });
      return;
    }
    if (path === "/api/stores/store-1/suspend") {
      store = storeResource("SUSPENDED", store.name, 3);
      await route.fulfill({ json: store, status: 201 });
      return;
    }
    if (path === "/api/stores/store-1/reactivate") {
      store = storeResource("ACTIVE", store.name, 4);
      await route.fulfill({ json: store, status: 201 });
      return;
    }
    if (path === "/api/stores/store-1/closure-request") {
      store = storeResource("CLOSURE_PENDING", store.name, 5);
      await route.fulfill({
        json: {
          blockers: ["OPEN_CASH_SESSION"],
          id: "closure-1",
          reason: "Fin d’activité",
          requestedAt: "2026-09-10T11:00:00+00:00",
          status: "IN_PROGRESS",
          storeId: "store-1",
          version: 1,
        },
        status: 201,
      });
      return;
    }

    await route.fulfill({ json: {}, status: 404 });
  });

  await page.goto("/login");
  await page.getByLabel("Adresse e-mail").fill("owner@zandu.test");
  await page.getByLabel("Mot de passe").fill("password");
  await page.getByRole("button", { name: "Se connecter" }).click();
  await page.getByRole("link", { name: "Magasins" }).click();
  await page.getByRole("link", { name: "Créer un magasin" }).click();
  await page.getByLabel("Code").fill("CENTRE");
  await page.getByLabel("Nom").fill("Nouveau magasin");
  await page.getByRole("button", { name: "Créer le magasin" }).click();
  await expect(page).toHaveURL(/\/app\/stores\/store-1$/);
  await expect(page.getByRole("heading", { name: "Nouveau magasin" })).toBeVisible();

  await page.getByRole("link", { name: "Modifier" }).click();
  await page.getByLabel("Nom").fill("Magasin rénové");
  await page.getByRole("button", { name: "Enregistrer les modifications" }).click();
  await expect(page.getByRole("heading", { name: "Magasin rénové" })).toBeVisible();

  await page.getByRole("button", { name: "Suspendre" }).click();
  await page.getByRole("button", { name: "Confirmer la suspension" }).click();
  await expect(page.getByText("SUSPENDED")).toBeVisible();
  await page.getByRole("button", { name: "Réactiver" }).click();
  await page.getByRole("button", { name: "Confirmer la réactivation" }).click();
  await expect(page.getByText("ACTIVE")).toBeVisible();

  await page.getByRole("button", { name: "Demander la fermeture" }).click();
  await page.getByLabel("Motif de fermeture").fill("Fin d’activité");
  await page.getByRole("button", { name: "Confirmer la demande de fermeture" }).click();
  await expect(page.getByText("Statut de la demande : IN_PROGRESS.")).toBeVisible();
  await expect(page.getByText("Une session de caisse est encore ouverte.")).toBeVisible();
});

test("a user without Store permission is kept out of the protected route", async ({ page }) => {
  await page.route("**/api/**", async (route) => {
    const path = new URL(route.request().url()).pathname;
    if (path === "/api/auth/refresh") {
      await route.fulfill({ json: {}, status: 401 });
      return;
    }
    if (path === "/api/auth/login") {
      await route.fulfill({ json: { token: token() } });
      return;
    }
    if (path === "/api/session") {
      await route.fulfill({ json: session([]) });
      return;
    }
    await route.fulfill({ json: {}, status: 403 });
  });

  await page.goto("/login");
  await page.getByLabel("Adresse e-mail").fill("denied@zandu.test");
  await page.getByLabel("Mot de passe").fill("password");
  await page.getByRole("button", { name: "Se connecter" }).click();
  await page.goto("/app/stores");
  await expect(page.getByRole("heading", { name: "Accès refusé" })).toBeVisible();
});

function session(permissions: readonly string[]) {
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
        defaultTimeZone: "Africa/Brazzaville",
        id: organizationId,
        name: "Zandu",
        status: "ACTIVE",
      },
    ],
    userId: "user-1",
  };
}

function storeResource(
  status: "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING",
  name: string,
  version: number,
) {
  return {
    address: null,
    code: "CENTRE",
    currency: "XAF",
    id: "store-1",
    locale: "fr_CG",
    name,
    organizationId,
    status,
    timeZone: "Africa/Brazzaville",
    updatedAt: "2026-09-10T08:00:00+00:00",
    version,
  };
}

function token(): string {
  return `header.${Buffer.from(JSON.stringify({ exp: Math.floor(Date.now() / 1000) + 60 })).toString("base64url")}.signature`;
}
