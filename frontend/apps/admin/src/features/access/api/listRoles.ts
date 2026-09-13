import type { FoundationApi, RoleResource } from "@zandu/api-client";

/** Roles are a read-only server projection. */
export function listRoles(api: FoundationApi): Promise<readonly RoleResource[]> {
  return api.listRoles();
}
