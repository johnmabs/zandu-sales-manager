import assert from "node:assert/strict";
import { access, readFile } from "node:fs/promises";
import test from "node:test";

const featureRoot = new URL("../src/features/stores/", import.meta.url);

test("Stores owns its route composition and extension points", async () => {
  await Promise.all(
    [
      "api/.gitkeep",
      "components/StoreListPage.tsx",
      "hooks/.gitkeep",
      "routes/StoresRoute.tsx",
      "schemas/.gitkeep",
      "README.md",
    ].map((path) => assert.doesNotReject(access(new URL(path, featureRoot)))),
  );
});

test("the Next.js Stores page delegates to the feature route", async () => {
  const page = await readFile(new URL("../app/admin/stores/page.tsx", import.meta.url), "utf8");

  assert.match(page, /features\/stores\/routes\/StoresRoute/);
  assert.match(page, /<StoresRoute\s*\/>/);
});
