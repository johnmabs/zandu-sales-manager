import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import test from "node:test";

const pageUrl = new URL("../app/page.tsx", import.meta.url);

test("admin root page redirects to the login route", async () => {
  const page = await readFile(pageUrl, "utf8");

  assert.match(page, /redirect\("\/login"\)/);
});
