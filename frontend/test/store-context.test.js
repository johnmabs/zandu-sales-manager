import assert from "node:assert/strict";
import test from "node:test";

import { StoreContextManager, StoreSelectionError } from "../packages/store-context/src/index.ts";

const organizationId = "0198c728-8f2d-7f43-92d8-3f0c75b80187";

function store({ id, status = "ACTIVE" }) {
  return {
    currency: "XOF",
    id,
    locale: "fr-CI",
    name: `Store ${id}`,
    organizationId,
    status,
    timeZone: "Africa/Abidjan",
  };
}

test("the context distinguishes no accessible store, one active store and a required selection", () => {
  const context = new StoreContextManager(organizationId);

  assert.equal(context.getState().status, "UNKNOWN");
  assert.equal(context.setAccessibleStores([]).status, "NO_ACCESSIBLE_STORES");

  const oneStore = context.setAccessibleStores([store({ id: "store-1" })]);
  assert.equal(oneStore.status, "ACTIVE");
  assert.equal(oneStore.activeStore.id, "store-1");

  const multipleStores = context.setAccessibleStores([
    store({ id: "store-1" }),
    store({ id: "store-2" }),
  ]);
  assert.equal(multipleStores.status, "ACTIVE");

  context.clear();
  assert.equal(context.getState().status, "SELECTION_REQUIRED");
});

test("the selector exposes only accessible active stores and rejects suspended or closed choices", () => {
  const context = new StoreContextManager(organizationId);
  context.setAccessibleStores([
    store({ id: "active" }),
    store({ id: "suspended", status: "SUSPENDED" }),
    store({ id: "closing", status: "CLOSURE_PENDING" }),
    store({ id: "closed", status: "CLOSED" }),
  ]);

  assert.deepEqual(
    context.getSelectableStores().map((candidate) => candidate.id),
    ["active"],
  );
  assert.equal(context.getState().activeStore.id, "active");
  assert.throws(() => context.selectStore("suspended"), StoreSelectionError);
  assert.throws(() => context.selectStore("closed"), StoreSelectionError);
});

test("a scope change clears a revoked, suspended or closed active store and requires reselection", () => {
  const context = new StoreContextManager(organizationId);
  context.setAccessibleStores([store({ id: "store-1" }), store({ id: "store-2" })]);
  context.selectStore("store-1");

  const revoked = context.setAccessibleStores([store({ id: "store-2" }), store({ id: "store-3" })]);
  assert.equal(revoked.status, "SELECTION_REQUIRED");
  assert.equal(revoked.activeStore, undefined);

  context.selectStore("store-2");
  const suspended = context.setAccessibleStores([
    store({ id: "store-2", status: "SUSPENDED" }),
    store({ id: "store-3" }),
  ]);
  assert.equal(suspended.status, "ACTIVE");
  assert.equal(suspended.activeStore.id, "store-3");

  const closed = context.setAccessibleStores([
    store({ id: "store-2", status: "CLOSED" }),
    store({ id: "store-3", status: "CLOSED" }),
  ]);
  assert.equal(closed.status, "NO_SELECTABLE_STORES");
  assert.equal(closed.activeStore, undefined);
});

test("the context does not accept stores from another organization or duplicate scope entries", () => {
  const context = new StoreContextManager(organizationId);

  assert.throws(
    () => context.setAccessibleStores([{ ...store({ id: "other" }), organizationId: "other-org" }]),
    StoreSelectionError,
  );
  assert.throws(
    () => context.setAccessibleStores([store({ id: "same" }), store({ id: "same" })]),
    StoreSelectionError,
  );
});
