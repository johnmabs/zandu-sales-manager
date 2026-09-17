import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const priceLists = new URL("../src/features/pricing/routes/PriceListsRoute.tsx", import.meta.url);
const productPrices = new URL(
  "../src/features/pricing/routes/ProductPricesRoute.tsx",
  import.meta.url,
);
const productDetails = new URL(
  "../src/features/catalog/products/ProductDetailsPage.tsx",
  import.meta.url,
);
const productsRoute = new URL("../src/features/catalog/routes/ProductsRoute.tsx", import.meta.url);

test("Product creation uses the Admin route namespace", async () => {
  const source = await readFile(productsRoute, "utf8");
  assert.match(source, /href="\/admin\/catalog\/products\/new"/);
  assert.doesNotMatch(source, /href="\/app\//);
});

test("Price Lists exposes create, update and every lifecycle transition", async () => {
  const source = await readFile(priceLists, "utf8");
  for (const contract of [
    "createPriceList",
    "updatePriceList",
    '"activate"',
    '"deactivate"',
    '"archive"',
    "expectedVersion",
  ]) {
    assert.match(source, new RegExp(contract));
  }
  assert.doesNotMatch(source, /disponible prochainement/);
});

test("Product Prices preserves exact amounts and resolves no-price separately", async () => {
  const source = await readFile(productPrices, "utf8");
  assert.match(source, /createProductPrice/);
  assert.match(source, /updateProductPrice/);
  assert.match(source, /getEffectiveProductPrice/);
  assert.match(source, /Aucun prix applicable/);
  assert.match(source, /distincte d’un prix à zéro/);
  assert.doesNotMatch(source, /parseFloat|Number\(data\.get\("amount"\)\)/);
});

test("Product details manages persisted barcodes and effective-price summaries", async () => {
  const source = await readFile(productDetails, "utf8");
  assert.match(source, /listProductBarcodes/);
  assert.match(source, /addProductBarcode/);
  assert.match(source, /removeProductBarcode/);
  assert.match(source, /inputMode="text"/);
  assert.match(source, /getEffectiveProductPrice/);
});
