import assert from "node:assert/strict";
import test from "node:test";

import {
  OrganizationContextManager,
  OrganizationSelectionError,
} from "../packages/organization-context/src/index.ts";

function organization({ id = "organization-1", status = "ACTIVE" } = {}) {
  return {
    defaultCurrency: "XAF",
    defaultLocale: "fr_CG",
    defaultTimeZone: "Africa/Brazzaville",
    id,
    name: `Organization ${id}`,
    status,
  };
}

test("organization selection stays explicit and accepts the server-validated actor tenant", () => {
  const context = new OrganizationContextManager();

  assert.equal(context.getState().status, "UNKNOWN");
  assert.equal(context.setOrganizations([organization()]).status, "SELECTION_REQUIRED");

  const selected = context.selectOrganization("organization-1");
  assert.equal(selected.status, "ACTIVE");
  assert.equal(selected.activeOrganizationId, "organization-1");
});

test("organization selection rejects route-like unknown, duplicate, and inactive values", () => {
  const context = new OrganizationContextManager();
  assert.throws(
    () => context.setOrganizations([organization(), organization()]),
    OrganizationSelectionError,
  );

  context.setOrganizations([organization(), organization({ id: "organization-2" })]);
  assert.throws(() => context.selectOrganization("route-tenant"), OrganizationSelectionError);

  const suspended = new OrganizationContextManager();
  assert.throws(
    () => suspended.setOrganizations([organization({ status: "SUSPENDED" })], "organization-1"),
    OrganizationSelectionError,
  );
});
