import { expirationLabel } from "../expirationPresentation";

import type { MembershipRoleAssignment } from "@zandu/api-client";

export function RoleAssignmentSummary({
  assignment,
}: Readonly<{ assignment: MembershipRoleAssignment }>) {
  const scope =
    assignment.scopeType === "ORGANIZATION"
      ? "Organisation entière"
      : assignment.scopeType === "SELECTED_STORES"
        ? `${assignment.storeIds.length} magasin(s) : ${assignment.storeIds.join(", ")}`
        : `Portée publiée : ${assignment.scopeType}`;
  return (
    <div className="zandu-role-assignment-summary">
      <strong>{assignment.roleId}</strong>
      <span> — {scope}</span>
      <span> — {expirationLabel(assignment.expiresAt)}</span>
    </div>
  );
}
