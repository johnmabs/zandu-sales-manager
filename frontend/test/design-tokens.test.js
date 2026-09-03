import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const tokensUrl = new URL("../packages/design-tokens/src/tokens.css", import.meta.url);
const adminStylesUrl = new URL("../apps/admin/app/globals.css", import.meta.url);
const posStylesUrl = new URL("../apps/pos/src/styles.css", import.meta.url);

test("shared design tokens define stable visual primitives", async () => {
  const tokens = await readFile(tokensUrl, "utf8");

  for (const token of [
    "--zandu-color-surface-canvas",
    "--zandu-space-4",
    "--zandu-radius-md",
    "--zandu-font-family-sans",
    "--zandu-breakpoint-md",
    "--zandu-z-index-modal",
  ]) {
    assert.match(tokens, new RegExp(`${token}:`));
  }
});

test("Admin and POS consume the same design-token stylesheet", async () => {
  const [adminStyles, posStyles] = await Promise.all([
    readFile(adminStylesUrl, "utf8"),
    readFile(posStylesUrl, "utf8"),
  ]);

  for (const styles of [adminStyles, posStyles]) {
    assert.match(styles, /@import "@zandu\/design-tokens\/tokens\.css"/);
    assert.match(styles, /var\(--zandu-color-text-primary\)/);
    assert.match(styles, /var\(--zandu-space-8\)/);
  }
});
