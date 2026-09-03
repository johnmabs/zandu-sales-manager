import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const appUrl = new URL("../src/App.tsx", import.meta.url);

test("POS shell renders its placeholder", async () => {
  const app = await readFile(appUrl, "utf8");

  assert.match(app, /<h1>Zandu POS<\/h1>/);
});
