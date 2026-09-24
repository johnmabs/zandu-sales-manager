import assert from "node:assert/strict";
import { access, readFile } from "node:fs/promises";
import test from "node:test";

const adminFeatureRoutes = [
  "organization/OrganizationRoute.tsx",
  "stores/index.ts",
  "access/routes/MembersRoute.tsx",
  "catalog/CatalogRoute.tsx",
  "pricing/PricingRoute.tsx",
  "inventory/InventoryRoute.tsx",
  "purchasing/PurchasingRoute.tsx",
  "cash/CashRoute.tsx",
  "sales/SalesRoute.tsx",
];

test("Admin routes delegate to feature-owned route components", async () => {
  await Promise.all(
    adminFeatureRoutes.map((featureRoute) =>
      assert.doesNotReject(
        access(new URL(`../apps/admin/src/features/${featureRoute}`, import.meta.url)),
      ),
    ),
  );

  await Promise.all(
    [
      "admin/src/components/admin-shell/AdminShell.tsx",
      "admin/src/components/tables/AdminTable.ts",
      "admin/src/components/routes/RoutePlaceholder.tsx",
    ].map((component) =>
      assert.doesNotReject(access(new URL(`../apps/${component}`, import.meta.url))),
    ),
  );
});

test("Store pages consume only the Stores feature public entrypoint", async () => {
  const storePages = ["page.tsx", "new/page.tsx", "[storeId]/page.tsx", "[storeId]/edit/page.tsx"];

  const sources = await Promise.all(
    storePages.map((page) =>
      readFile(new URL(`../apps/admin/app/admin/stores/${page}`, import.meta.url), "utf8"),
    ),
  );

  for (const source of sources) {
    assert.match(source, /from "(?:\.\.\/)+src\/features\/stores";/);
    assert.doesNotMatch(source, /src\/features\/stores\//);
  }
});

test("POS separates application composition from its terminal feature", async () => {
  await Promise.all([
    assert.doesNotReject(access(new URL("../apps/pos/src/app/App.tsx", import.meta.url))),
    assert.doesNotReject(
      access(new URL("../apps/pos/src/features/terminal/PosShell.tsx", import.meta.url)),
    ),
    assert.doesNotReject(
      access(new URL("../apps/pos/src/features/terminal/operational-status.ts", import.meta.url)),
    ),
  ]);
});
