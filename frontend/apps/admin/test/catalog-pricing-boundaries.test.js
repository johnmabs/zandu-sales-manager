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

test("Pricing does not embed Catalog or Inventory internals", async () => {
  const files = await sources(new URL("../src/features/pricing/", import.meta.url));
  for (const source of files) {
    assert.doesNotMatch(source, /from\s+["'][^"']*(?:catalog|inventory)\//);
    assert.doesNotMatch(source, /\bcostPrice\b|parseFloat\(/);
  }
});

test("Catalog does not embed Pricing or Inventory internals", async () => {
  const files = await sources(new URL("../src/features/catalog/", import.meta.url));
  for (const source of files) {
    assert.doesNotMatch(source, /from\s+["'][^"']*(?:pricing|inventory)\//);
    assert.doesNotMatch(source, /\bcostPrice\b|parseFloat\(/);
  }
});
