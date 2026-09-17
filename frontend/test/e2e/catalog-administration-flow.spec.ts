import { expect, test } from "@playwright/test";

import type { Page } from "@playwright/test";

const organizationId = "organization-1";
const categoryId = "category-1";
const productId = "product-1";
const packagingId = "packaging-1";
const barcodeId = "barcode-1";

test("an administrator completes the Catalog flow", async ({ page }) => {
  let authenticated = false;
  let categoryCreated = false;
  let productCreated = false;
  let productActive = false;
  let packagingCreated = false;
  let barcodeCreated = false;
  let categoryPayload: unknown;
  let productPayload: unknown;
  let packagingPayload: unknown;
  let barcodePayload: unknown;
  const productSearches: string[] = [];

  await page.route("**/api/**", async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    const path = url.pathname;
    const method = request.method();

    if (path === "/api/auth/refresh") {
      await route.fulfill(authenticated ? { json: { token: token() } } : { json: {}, status: 401 });
      return;
    }
    if (path === "/api/auth/login") {
      authenticated = true;
      await route.fulfill({ json: { token: token() } });
      return;
    }
    if (path === "/api/session") {
      await route.fulfill({ json: session() });
      return;
    }
    if (path === "/api/categories" && method === "GET") {
      await route.fulfill({ json: categoryCreated ? [categoryResource()] : [] });
      return;
    }
    if (path === "/api/categories" && method === "POST") {
      categoryPayload = request.postDataJSON();
      categoryCreated = true;
      await route.fulfill({ json: categoryResource(), status: 201 });
      return;
    }
    if (path === "/api/products" && method === "GET") {
      const search = url.searchParams.get("search");
      if (search !== null) productSearches.push(search);
      const matches =
        search === null ||
        productResource(productActive)
          .name.toLocaleLowerCase()
          .includes(search.toLocaleLowerCase()) ||
        productResource(productActive)
          .productCode.toLocaleLowerCase()
          .includes(search.toLocaleLowerCase());
      await route.fulfill({
        json: productCreated && matches ? [productResource(productActive)] : [],
      });
      return;
    }
    if (path === "/api/products" && method === "POST") {
      productPayload = request.postDataJSON();
      productCreated = true;
      await route.fulfill({ json: productResource(productActive), status: 201 });
      return;
    }
    if (path === `/api/products/${productId}` && method === "GET") {
      await route.fulfill({ json: productResource(productActive) });
      return;
    }
    if (path === `/api/products/${productId}/activate` && method === "POST") {
      productActive = true;
      await route.fulfill({ json: productResource(productActive), status: 201 });
      return;
    }
    if (path === `/api/products/${productId}/packagings` && method === "GET") {
      await route.fulfill({ json: packagingCreated ? [packagingResource()] : [] });
      return;
    }
    if (path === `/api/products/${productId}/packagings` && method === "POST") {
      packagingPayload = request.postDataJSON();
      packagingCreated = true;
      await route.fulfill({ json: packagingResource(), status: 201 });
      return;
    }
    if (
      path === `/api/products/${productId}/packagings/${packagingId}/barcodes` &&
      method === "GET"
    ) {
      await route.fulfill({ json: barcodeCreated ? [barcodeResource()] : [] });
      return;
    }
    if (
      path === `/api/products/${productId}/packagings/${packagingId}/barcodes` &&
      method === "POST"
    ) {
      barcodePayload = request.postDataJSON();
      barcodeCreated = true;
      await route.fulfill({ json: barcodeResource(), status: 201 });
      return;
    }

    await route.fulfill({ json: {}, status: 404 });
  });

  await login(page);
  await page
    .getByRole("navigation", { name: "Navigation principale" })
    .getByRole("link", { name: "Catalogue" })
    .click();
  await page
    .getByRole("navigation", { name: "Navigation Catalogue" })
    .getByRole("link", { name: "Catégories" })
    .click();
  await page.getByLabel("Nom", { exact: true }).fill("Santé");
  await page.getByRole("button", { name: "Créer la catégorie" }).click();
  await expect(page.getByText("Santé", { exact: true })).toBeVisible();

  await page
    .getByRole("navigation", { name: "Navigation Catalogue" })
    .getByRole("link", { name: "Produits" })
    .click();
  await page.getByRole("link", { name: "Nouveau produit" }).click();
  await page.getByLabel("Code", { exact: true }).fill("MED-001");
  await page.getByLabel("Nom", { exact: true }).fill("Paracétamol");
  await page.getByLabel("Description").fill("Comprimé antalgique");
  await page.getByLabel("Identifiant de l’unité de base").fill("unit-piece");
  await page.getByLabel("Identifiant de catégorie", { exact: true }).fill(categoryId);
  await page.getByRole("button", { name: "Créer le produit" }).click();
  await expect(page).toHaveURL(new RegExp(`/admin/catalog/products/${productId}$`));
  await expect(page.getByRole("heading", { name: "Paracétamol", level: 1 })).toBeVisible();

  const packagingForm = page.getByRole("form", { name: "Nouveau conditionnement" });
  await packagingForm.getByLabel("code", { exact: true }).fill("BOX-10");
  await packagingForm.getByLabel("name", { exact: true }).fill("Boîte de 10");
  await packagingForm.getByLabel("unitId", { exact: true }).fill("unit-piece");
  await packagingForm.getByLabel("conversionFactor", { exact: true }).fill("10.000");
  await packagingForm.getByLabel("precision", { exact: true }).fill("3");
  await packagingForm.getByLabel("minimumQuantity", { exact: true }).fill("0.125");
  await packagingForm.getByLabel("quantityIncrement", { exact: true }).fill("0.125");
  await packagingForm.getByLabel("Vente", { exact: true }).check();
  await packagingForm.getByRole("button", { name: "Ajouter" }).click();
  await expect(page.getByText("Boîte de 10", { exact: false }).first()).toBeVisible();

  const barcodeInput = page.getByLabel("Nouveau code-barres");
  await barcodeInput.fill("0012345678905");
  await barcodeInput
    .locator("xpath=ancestor::form")
    .getByRole("button", { name: "Ajouter" })
    .click();
  await expect(page.getByText("0012345678905", { exact: true })).toBeVisible();

  await page.getByRole("button", { name: "Activer", exact: true }).click();
  await expect(page.getByText("MED-001 · ACTIVE", { exact: true })).toBeVisible();

  await page
    .getByRole("navigation", { name: "Navigation Catalogue" })
    .getByRole("link", { name: "Produits" })
    .click();
  await page.getByRole("searchbox", { name: "Rechercher un produit" }).fill("Paracétamol");
  await page.getByRole("button", { name: "Appliquer les filtres" }).click();
  await expect(page.getByRole("link", { name: "Paracétamol" })).toBeVisible();

  expect(categoryPayload).toEqual({ name: "Santé", parentCategoryId: null });
  expect(productPayload).toEqual({
    baseUnitId: "unit-piece",
    categoryId,
    description: "Comprimé antalgique",
    inventoryTracked: true,
    name: "Paracétamol",
    productCode: "MED-001",
    taxCategoryId: null,
    type: "PHYSICAL",
  });
  expect(packagingPayload).toEqual({
    allowedForPurchase: false,
    allowedForSale: true,
    code: "BOX-10",
    conversionFactor: "10.000",
    minimumQuantity: "0.125",
    name: "Boîte de 10",
    precision: 3,
    quantityIncrement: "0.125",
    unitId: "unit-piece",
  });
  expect(barcodePayload).toEqual({ barcode: "0012345678905" });
  expect(productSearches).toContain("Paracétamol");
});

async function login(page: Page) {
  await page.goto("/login");
  await page.getByLabel("Adresse e-mail").fill("catalog-admin@zandu.test");
  await page.getByLabel("Mot de passe").fill("password");
  await page.getByRole("button", { name: "Se connecter" }).click();
  await expect(page).toHaveURL(/\/admin$/);
}

function session() {
  return {
    authorizationVersion: 1,
    effectiveAccess: {
      accessibleStoreIds: [],
      authorizationVersion: 1,
      organizationId,
      permissions: [
        "CATALOG_READ",
        "CATEGORY_CREATE",
        "PRODUCT_READ",
        "PRODUCT_CREATE",
        "PRODUCT_UPDATE",
        "PRODUCT_ACTIVATE",
      ],
      scope: { type: "ORGANIZATION" },
    },
    email: "catalog-admin@zandu.test",
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
    userId: "user-1",
  };
}

function categoryResource() {
  return {
    createdAt: "2026-09-17T08:00:00Z",
    id: categoryId,
    name: "Santé",
    organizationId,
    parentCategoryId: null,
    status: "ACTIVE",
    updatedAt: null,
    version: 1,
  };
}

function productResource(active: boolean) {
  return {
    activatedAt: active ? "2026-09-17T08:05:00Z" : null,
    baseUnitId: "unit-piece",
    categoryId,
    createdAt: "2026-09-17T08:01:00Z",
    description: "Comprimé antalgique",
    id: productId,
    inventoryTracked: true,
    name: "Paracétamol",
    organizationId,
    productCode: "MED-001",
    status: active ? "ACTIVE" : "DRAFT",
    taxCategoryId: null,
    type: "PHYSICAL",
    updatedAt: active ? "2026-09-17T08:05:00Z" : null,
    version: active ? 2 : 1,
  };
}

function packagingResource() {
  return {
    allowedForPurchase: false,
    allowedForSale: true,
    base: false,
    code: "BOX-10",
    conversionFactor: "10.000",
    createdAt: "2026-09-17T08:02:00Z",
    id: packagingId,
    minimumQuantity: "0.125",
    name: "Boîte de 10",
    organizationId,
    precision: 3,
    productId,
    quantityIncrement: "0.125",
    status: "ACTIVE",
    unitId: "unit-piece",
    updatedAt: null,
    version: 1,
  };
}

function barcodeResource() {
  return {
    barcode: "0012345678905",
    id: barcodeId,
    packagingId,
    productId,
    status: "ACTIVE",
    version: 1,
  };
}

function token(): string {
  return `header.${Buffer.from(
    JSON.stringify({ authorizationVersion: 1, exp: Math.floor(Date.now() / 1000) + 60 }),
  ).toString("base64url")}.signature`;
}
