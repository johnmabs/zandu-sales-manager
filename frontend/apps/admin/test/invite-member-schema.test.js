import assert from "node:assert/strict";
import test from "node:test";

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
