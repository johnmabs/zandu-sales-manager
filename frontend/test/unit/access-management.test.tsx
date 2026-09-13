import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";

import {
  MembershipStatusBadge,
  ScopeBadge,
} from "../../apps/admin/src/features/access/components/AccessBadges";
import { RoleAssignmentSummary } from "../../apps/admin/src/features/access/components/RoleAssignmentSummary";
import { availableMemberActions } from "../../apps/admin/src/features/access/members/memberAuthorization";

describe("Access presentation primitives", () => {
  it("renders textual status, scope and assignment summaries", () => {
    const assignment = {
      assignmentId: "a-1",
      expiresAt: null,
      roleId: "role-1",
      scopeType: "SELECTED_STORES",
      storeIds: ["store-1"],
    };
    const output = renderToStaticMarkup(
      <>
        <MembershipStatusBadge status="ACTIVE" />
        <ScopeBadge scopeType="SELECTED_STORES" />
        <RoleAssignmentSummary assignment={assignment} />
      </>,
    );
    expect(output).toContain("ACTIVE");
    expect(output).toContain("Magasins sélectionnés");
    expect(output).toContain("Sans expiration");
  });

  it("never exposes forbidden or invalid lifecycle actions", () => {
    expect(availableMemberActions("ACTIVE", [])).toEqual({
      assignRole: false,
      reactivate: false,
      removeRole: false,
      revoke: false,
      suspend: false,
    });
    expect(availableMemberActions("REVOKED", ["MEMBER_SUSPEND", "MEMBER_REVOKE"]).reactivate).toBe(
      false,
    );
  });
});
