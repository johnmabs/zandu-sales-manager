import assert from "node:assert/strict";
import test from "node:test";

import { expirationLabel } from "../src/features/access/expirationPresentation.ts";
import {
  assignRoleSchema,
  toRoleAssignmentInput,
} from "../src/features/access/schemas/assignRoleSchema.ts";
import { toInvitationCreateInput } from "../src/features/access/schemas/inviteMemberSchema.ts";

test("invite input keeps each selected role assignment and normalizes optional expiry", () => {
  assert.deepEqual(
    toInvitationCreateInput({
      email: " member@zandu.test ",
      expiresAt: "",
      roleCodes: [" STORE_MANAGER ", "CASHIER"],
    }),
    {
      email: "member@zandu.test",
      expiresAt: null,
      roleAssignments: [
        { roleCode: "STORE_MANAGER", storeIds: [] },
        { roleCode: "CASHIER", storeIds: [] },
      ],
    },
  );
});

test("assignment expiry distinguishes no expiry from an instant", () => {
  assert.equal(expirationLabel(null), "Sans expiration");
  assert.match(expirationLabel("2026-12-31T12:30:00+00:00"), /^Expire le /);
});

test("role assignment scopes cannot produce ambiguous store payloads", () => {
  assert.equal(
    assignRoleSchema.safeParse({
      expiresAt: "",
      roleId: "role-1",
      scopeType: "SELECTED_STORES",
      storeIds: [],
    }).success,
    false,
  );
  assert.deepEqual(
    toRoleAssignmentInput({
      expiresAt: "",
      roleId: "role-1",
      scopeType: "ORGANIZATION",
      storeIds: ["stale-store"],
    }),
    { expiresAt: null, roleId: "role-1", scopeType: "ORGANIZATION", storeIds: [] },
  );
});
