import assert from "node:assert/strict";
import test from "node:test";

import {
  EffectiveAccessError,
  EffectiveAccessProvider,
  can,
  canViewNavigation,
} from "../packages/authorization/src/index.ts";

const organizationId = "0198c728-8f2d-7f43-92d8-3f0c75b80187";

function access(overrides = {}) {
  return {
    accessibleStoreIds: ["store-1", "store-2"],
    authorizationVersion: 4,
    organizationId,
    permissions: ["PRODUCT_CREATE", "PRODUCT_READ"],
    scope: { type: "ORGANIZATION" },
    ...overrides,
  };
}

test("authorization checks permissions rather than roles and denies unresolved access", () => {
  assert.equal(can(undefined, "PRODUCT_CREATE"), false);
  assert.equal(can(access(), "PRODUCT_CREATE"), true);
  assert.equal(can(access(), "PRODUCT_ARCHIVE"), false);
});

test("authorization checks organization and store scopes before enabling an action", () => {
  assert.equal(can(access(), "PRODUCT_CREATE", { organizationId, storeId: "store-1" }), true);
  assert.equal(can(access(), "PRODUCT_CREATE", { organizationId: "another-org" }), false);
  assert.equal(can(access(), "PRODUCT_CREATE", { storeId: "outside-access" }), false);

  const selectedStores = access({
    scope: { storeIds: ["store-1"], type: "SELECTED_STORES" },
  });
  assert.equal(can(selectedStores, "PRODUCT_CREATE", { storeId: "store-1" }), true);
  assert.equal(can(selectedStores, "PRODUCT_CREATE", { storeId: "store-2" }), false);
});

test("navigation is visible for any relevant permitted capability", () => {
  const catalog = {
    capabilities: [{ permission: "PRODUCT_ARCHIVE" }, { permission: "PRODUCT_READ" }],
  };
  const unavailable = { capabilities: [{ permission: "STOCK_TRANSFER_SHIP", storeId: "store-1" }] };

  assert.equal(canViewNavigation(access(), catalog), true);
  assert.equal(canViewNavigation(access(), unavailable), false);
});

test("the provider rejects malformed server projections before exposing them", () => {
  assert.throws(
    () =>
      EffectiveAccessProvider({
        access: access({ permissions: ["PRODUCT_READ", "PRODUCT_READ"] }),
        children: null,
      }),
    EffectiveAccessError,
  );
  assert.throws(
    () =>
      EffectiveAccessProvider({
        access: access({ scope: { storeIds: ["outside-access"], type: "SELECTED_STORES" } }),
        children: null,
      }),
    EffectiveAccessError,
  );
});
