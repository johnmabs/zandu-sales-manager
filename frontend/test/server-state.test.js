import assert from "node:assert/strict";
import test from "node:test";

import {
  createServerStateClient,
  queryKeys,
  transitionOrganizationCache,
} from "../packages/server-state/src/index.ts";

const organizationA = "0198c728-8f2d-7f43-92d8-3f0c75b80187";
const organizationB = "0198c728-8f2d-7f43-92d8-3f0c75b80188";

test("query keys centrally include organization and store scopes", () => {
  assert.deepEqual(queryKeys.organizations.all(), ["organizations"]);
  assert.deepEqual(queryKeys.products.list(organizationA, { page: 2 }), [
    "products",
    organizationA,
    { page: 2 },
  ]);
  assert.deepEqual(queryKeys.members.list(organizationA), ["members", organizationA]);
  assert.deepEqual(queryKeys.roles.list(organizationA), ["roles", organizationA]);
  assert.deepEqual(queryKeys.stock.list(organizationA, "store-1", { search: "rice" }), [
    "stock",
    organizationA,
    "store-1",
    { search: "rice" },
  ]);
  assert.deepEqual(queryKeys.sales.detail(organizationA, "store-1", "sale-1"), [
    "sales",
    organizationA,
    "store-1",
    "sale-1",
  ]);
});

test("the server-state client retries reads but never retries mutations automatically", () => {
  const client = createServerStateClient();

  assert.equal(client.getDefaultOptions().queries?.retry, 2);
  assert.equal(client.getDefaultOptions().queries?.staleTime, 30_000);
  assert.equal(client.getDefaultOptions().mutations?.retry, false);
});

test("an organization transition removes other tenant data and invalidates retained data", async () => {
  const client = createServerStateClient();
  const productsA = queryKeys.products.list(organizationA);
  const stockB = queryKeys.stock.list(organizationB, "store-b");

  client.setQueryData(productsA, [{ id: "product-a" }]);
  client.setQueryData(stockB, [{ id: "stock-b" }]);

  await transitionOrganizationCache(client, organizationA);

  assert.deepEqual(client.getQueryData(productsA), [{ id: "product-a" }]);
  assert.equal(client.getQueryState(productsA)?.isInvalidated, true);
  assert.equal(client.getQueryData(stockB), undefined);
});
