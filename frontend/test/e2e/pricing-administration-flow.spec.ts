import { expect, test } from "@playwright/test";

import type { Page } from "@playwright/test";

const organizationId = "organization-1";
const priceListId = "price-list-1";
const productId = "product-1";
const packagingId = "packaging-1";
const productPriceId = "product-price-1";

test("an administrator completes the Pricing flow", async ({ page }) => {
  let authenticated = false;
  let priceListCreated = false;
  let priceListActive = false;
  let productPriceCreated = false;
  let productPriceActive = false;
  let priceListPayload: unknown;
  let productPricePayload: unknown;
  let effectivePriceRequests = 0;

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
    if (path === "/api/price-lists" && method === "GET") {
      await route.fulfill({ json: priceListCreated ? [priceListResource(priceListActive)] : [] });
      return;
    }
    if (path === "/api/price-lists" && method === "POST") {
      priceListPayload = request.postDataJSON();
      priceListCreated = true;
      await route.fulfill({ json: priceListResource(priceListActive), status: 201 });
      return;
    }
    if (path === `/api/price-lists/${priceListId}/activate` && method === "POST") {
      priceListActive = true;
      await route.fulfill({ json: priceListResource(priceListActive), status: 201 });
      return;
    }
    if (path === "/api/product-prices" && method === "GET") {
      await route.fulfill({
        json: productPriceCreated ? [productPriceResource(productPriceActive)] : [],
      });
      return;
    }
    if (path === "/api/product-prices" && method === "POST") {
      productPricePayload = request.postDataJSON();
      productPriceCreated = true;
      await route.fulfill({ json: productPriceResource(productPriceActive), status: 201 });
      return;
    }
    if (path === `/api/product-prices/${productPriceId}/activate` && method === "POST") {
      productPriceActive = true;
      await route.fulfill({ json: productPriceResource(productPriceActive), status: 201 });
      return;
    }
    if (path === "/api/products" && method === "GET") {
      await route.fulfill({ json: [productResource()] });
      return;
    }
    if (path === `/api/products/${productId}/packagings` && method === "GET") {
      await route.fulfill({ json: [packagingResource()] });
      return;
    }
    if (
      path === `/api/products/${productId}/packagings/${packagingId}/effective-price` &&
      method === "GET"
    ) {
      effectivePriceRequests += 1;
      await route.fulfill({ json: effectivePriceResource() });
      return;
    }

    await route.fulfill({ json: {}, status: 404 });
  });

  await login(page);
  await page
    .getByRole("navigation", { name: "Navigation principale" })
    .getByRole("link", { name: "Tarification" })
    .click();
  await page
    .getByRole("navigation", { name: "Navigation Tarification" })
    .getByRole("link", { name: "Listes de prix" })
    .click();

  const priceListForm = page
    .getByRole("heading", { name: "Créer une liste de prix" })
    .locator("xpath=ancestor::form");
  await priceListForm.getByLabel("Code", { exact: true }).fill("RETAIL");
  await priceListForm.getByLabel("Nom", { exact: true }).fill("Public");
  await priceListForm.getByLabel("Devise", { exact: true }).fill("xaf");
  await priceListForm.getByLabel("Priorité", { exact: true }).fill("10");
  await priceListForm.getByRole("button", { name: "Créer", exact: true }).click();
  await expect(page.getByRole("heading", { name: "Public", level: 2 })).toBeVisible();
  await page.getByRole("button", { name: "Activer", exact: true }).click();
  await expect(
    page.getByText("RETAIL · XAF · priorité 10 · ACTIVE", { exact: true }),
  ).toBeVisible();

  await page
    .getByRole("navigation", { name: "Navigation Tarification" })
    .getByRole("link", { name: "Prix produits" })
    .click();

  const productPriceForm = page
    .getByRole("heading", { name: "Créer un prix produit" })
    .locator("xpath=ancestor::form");
  await productPriceForm.getByRole("combobox", { name: "Liste de prix" }).selectOption(priceListId);
  await productPriceForm.getByRole("combobox", { name: "Produit" }).selectOption(productId);
  await productPriceForm
    .getByRole("combobox", { name: "Conditionnement" })
    .selectOption(packagingId);
  await productPriceForm.getByLabel("Montant exact").fill("001.250");
  await productPriceForm.getByRole("button", { name: "Créer", exact: true }).click();

  const productPrices = page.getByRole("list", { name: "Prix produits" });
  await expect(
    productPrices.getByRole("heading", { name: "Paracétamol · Public", level: 2 }),
  ).toBeVisible();
  await expect(productPrices.getByText("001.250 XAF · INACTIVE", { exact: true })).toBeVisible();
  await productPrices.getByRole("button", { name: "Activer", exact: true }).click();
  await expect(productPrices.getByText("001.250 XAF · ACTIVE", { exact: true })).toBeVisible();

  const resolver = page
    .getByRole("heading", { name: "Prix effectif" })
    .locator("xpath=ancestor::section");
  await resolver.getByRole("combobox", { name: "Produit" }).selectOption(productId);
  await resolver.getByRole("combobox", { name: "Conditionnement" }).selectOption(packagingId);
  await resolver.getByRole("button", { name: "Résoudre côté serveur" }).click();
  await expect(resolver.getByText("001.250 XAF", { exact: true })).toBeVisible();
  await expect(resolver).toContainText(`· liste ${priceListId}`);

  expect(priceListPayload).toEqual({
    code: "RETAIL",
    currency: "XAF",
    name: "Public",
    priority: 10,
    validFrom: null,
    validTo: null,
  });
  expect(productPricePayload).toEqual({
    amount: "001.250",
    currency: "XAF",
    packagingId,
    priceListId,
    productId,
    validFrom: null,
    validTo: null,
  });
  expect(priceListActive).toBe(true);
  expect(productPriceActive).toBe(true);
  expect(effectivePriceRequests).toBe(1);
});

async function login(page: Page) {
  await page.goto("/login");
  await page.getByLabel("Adresse e-mail").fill("pricing-admin@zandu.test");
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
        "PRICE_LIST_READ",
        "PRICE_LIST_CREATE",
        "PRICE_LIST_UPDATE",
        "PRICE_LIST_ACTIVATE",
        "PRODUCT_READ",
        "PRODUCT_PRICE_READ",
        "PRODUCT_PRICE_CREATE",
        "PRODUCT_PRICE_UPDATE",
      ],
      scope: { type: "ORGANIZATION" },
    },
    email: "pricing-admin@zandu.test",
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

function priceListResource(active: boolean) {
  return {
    code: "RETAIL",
    createdAt: "2026-09-17T09:00:00Z",
    currency: "XAF",
    id: priceListId,
    name: "Public",
    organizationId,
    priority: 10,
    scope: "ORGANIZATION",
    status: active ? "ACTIVE" : "DRAFT",
    validFrom: null,
    validTo: null,
    version: active ? 2 : 1,
  };
}

function productResource() {
  return {
    activatedAt: "2026-09-17T08:05:00Z",
    baseUnitId: "unit-piece",
    categoryId: "category-1",
    createdAt: "2026-09-17T08:01:00Z",
    description: "Comprimé antalgique",
    id: productId,
    inventoryTracked: true,
    name: "Paracétamol",
    organizationId,
    productCode: "MED-001",
    status: "ACTIVE",
    taxCategoryId: null,
    type: "PHYSICAL",
    updatedAt: "2026-09-17T08:05:00Z",
    version: 2,
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

function productPriceResource(active: boolean) {
  return {
    amount: "001.250",
    createdAt: "2026-09-17T09:05:00Z",
    currency: "XAF",
    id: productPriceId,
    organizationId,
    packagingId,
    priceListId,
    productId,
    status: active ? "ACTIVE" : "INACTIVE",
    validFrom: null,
    validTo: null,
    version: active ? 2 : 1,
  };
}

function effectivePriceResource() {
  return {
    amount: "001.250",
    currency: "XAF",
    priceListId,
    productPriceId,
    sourceVersion: 2,
  };
}

function token(): string {
  return `header.${Buffer.from(
    JSON.stringify({ authorizationVersion: 1, exp: Math.floor(Date.now() / 1000) + 60 }),
  ).toString("base64url")}.signature`;
}
