import assert from "node:assert/strict";
import test from "node:test";

import {
  resolveStoreAccess,
  storePermissions,
} from "../apps/admin/src/features/stores/storeAuthorization.ts";

const organizationId = "organization-1";

function access(overrides = {}) {
  return {
    accessibleStoreIds: ["store-1"],
    authorizationVersion: 3,
    organizationId,
    permissions: Object.values(storePermissions),
    scope: { type: "ORGANIZATION" },
    ...overrides,
  };
}

test("Store access remains unresolved until authorization and organization context are loaded", () => {
  assert.equal(resolveStoreAccess(undefined, organizationId, storePermissions.read), "UNRESOLVED");
  assert.equal(resolveStoreAccess(access(), undefined, storePermissions.read), "UNRESOLVED");
});

test("Store access uses the shared permission names and denies a missing permission locally", () => {
  assert.deepEqual(storePermissions, {
    close: "STORE_CLOSE",
    create: "STORE_CREATE",
    read: "STORE_READ",
    suspend: "STORE_SUSPEND",
    update: "STORE_UPDATE",
  });
  assert.equal(resolveStoreAccess(access(), organizationId, storePermissions.update), "ALLOWED");
  assert.equal(
    resolveStoreAccess(
      access({ permissions: [storePermissions.read] }),
      organizationId,
      storePermissions.update,
    ),
    "DENIED",
  );
});

test("Store access fails closed outside the active organization or effective Store scope", () => {
  assert.equal(
    resolveStoreAccess(access(), "organization-2", storePermissions.read, "store-1"),
    "OUT_OF_SCOPE",
  );
  assert.equal(
    resolveStoreAccess(access(), organizationId, storePermissions.read, "store-2"),
    "OUT_OF_SCOPE",
  );
  assert.equal(
    resolveStoreAccess(
      access({ scope: { storeIds: [], type: "SELECTED_STORES" } }),
      organizationId,
      storePermissions.read,
      "store-1",
    ),
    "OUT_OF_SCOPE",
  );
});
