import assert from "node:assert/strict";
import { access, readFile } from "node:fs/promises";
import test from "node:test";

const featureRoot = new URL("../src/features/stores/", import.meta.url);

test("Stores owns its route composition and extension points", async () => {
  await Promise.all(
    [
      "api/listStores.ts",
      "components/StoreListPage.tsx",
      "hooks/useStoreList.ts",
      "routes/StoresRoute.tsx",
      "schemas/createStoreSchema.ts",
      "index.ts",
      "README.md",
    ].map((path) => assert.doesNotReject(access(new URL(path, featureRoot)))),
  );
});

test("the Next.js Stores pages delegate through the public feature entrypoint", async () => {
  const pages = await Promise.all(
    ["page.tsx", "new/page.tsx", "[storeId]/page.tsx", "[storeId]/edit/page.tsx"].map((path) =>
      readFile(new URL(`../app/admin/stores/${path}`, import.meta.url), "utf8"),
    ),
  );

  for (const page of pages) {
    assert.match(page, /src\/features\/stores";/);
    assert.doesNotMatch(page, /src\/features\/stores\//);
  }
});
