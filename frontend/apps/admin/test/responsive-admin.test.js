import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const stylesUrl = new URL("../app/globals.css", import.meta.url);

test("Admin keeps Stores usable at laptop and tablet widths", async () => {
  const styles = await readFile(stylesUrl, "utf8");

  assert.match(styles, /@media \(max-width: 64rem\)/);
  assert.match(styles, /@media \(max-width: 48rem\)/);
  assert.match(styles, /\.zandu-admin-shell__sidebar[\s\S]*overflow-x: auto/);
  assert.match(styles, /\.zandu-admin-table[\s\S]*min-width: 34rem/);
  assert.match(styles, /\.zandu-store-details__section dl[\s\S]*grid-template-columns: 1fr/);
  assert.match(styles, /\.zandu-dialog[\s\S]*max-height: min\(90vh, 42rem\)/);
});

test("Access Management uses tablet spacing and progressive permission disclosure", async () => {
  const [styles, catalog] = await Promise.all([
    readFile(stylesUrl, "utf8"),
    readFile(new URL("../src/features/access/roles/RoleCatalog.tsx", import.meta.url), "utf8"),
  ]);
  assert.match(styles, /\.zandu-member-details/);
  assert.match(styles, /\.zandu-invite-member-form/);
  assert.match(catalog, /<details/);
  assert.match(catalog, /<summary>/);
});
