import assert from "node:assert/strict";
import { readFile, readdir } from "node:fs/promises";
import test from "node:test";

async function sources(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  const nested = await Promise.all(
    entries.map(async (entry) => {
      const file = new URL(entry.name + (entry.isDirectory() ? "/" : ""), directory);
      return entry.isDirectory()
        ? sources(file)
        : /\.tsx?$/.test(entry.name)
          ? [await readFile(file, "utf8")]
          : [];
    }),
  );
  return nested.flat();
}

test("Inventory does not import Catalog, Pricing or Costing internals", async () => {
  const files = await sources(new URL("../src/features/inventory/", import.meta.url));
  for (const source of files) {
    assert.doesNotMatch(source, /from\s+["'][^"']*(?:catalog|pricing|inventory-costing)\//);
    assert.doesNotMatch(source, /parseFloat\(|\bcostPrice\b/);
  }
});

test("Inventory exposes its stock contracts through the public boundary", async () => {
  const source = await readFile(
    new URL("../src/features/inventory/index.ts", import.meta.url),
    "utf8",
  );
  for (const contract of [
    "StockResource",
    "StockMovementResource",
    "StockMovementType",
    "StockMovementSource",
  ]) {
    assert.match(source, new RegExp(`\\b${contract}\\b`));
  }
  assert.doesNotMatch(source, /PriceListResource|StockValuationResource|ProductResource/);
});
