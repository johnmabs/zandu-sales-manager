import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const componentsUrl = new URL("../packages/ui/src/index.tsx", import.meta.url);
const stylesUrl = new URL("../packages/ui/src/styles.css", import.meta.url);

test("shared UI package exports generic Foundation primitives", async () => {
  const components = await readFile(componentsUrl, "utf8");

  for (const primitive of [
    "Button",
    "IconButton",
    "Input",
    "Textarea",
    "Select",
    "Checkbox",
    "Radio",
    "Dialog",
    "Drawer",
    "DropdownMenu",
    "Tooltip",
    "Badge",
    "Alert",
    "Toast",
    "Spinner",
    "Skeleton",
    "EmptyState",
    "ErrorState",
    "Pagination",
  ]) {
    assert.match(components, new RegExp(`export (const|function) ${primitive}`));
  }
});

test("UI primitives use shared tokens and accessible interaction contracts", async () => {
  const [components, styles] = await Promise.all([
    readFile(componentsUrl, "utf8"),
    readFile(stylesUrl, "utf8"),
  ]);

  assert.match(components, /aria-modal="true"/);
  assert.match(components, /document\.addEventListener\("keydown", manageKeyboardFocus\)/);
  assert.match(components, /event\.key !== "Tab"/);
  assert.match(components, /returnFocusReference\.current\?\.focus\(\)/);
  assert.match(components, /aria-label="Pagination"/);
  assert.match(styles, /@import "@zandu\/design-tokens\/tokens\.css"/);
  assert.match(styles, /:focus-visible/);
  assert.match(styles, /var\(--zandu-color-focus\)/);
});
