import { can } from "@zandu/authorization";

import type { EffectiveAccess } from "@zandu/authorization";

export const storePermissions = {
  close: "STORE_CLOSE",
  create: "STORE_CREATE",
  read: "STORE_READ",
  suspend: "STORE_SUSPEND",
  update: "STORE_UPDATE",
} as const;

export type StorePermission = (typeof storePermissions)[keyof typeof storePermissions];
export type StoreAccessState = "ALLOWED" | "DENIED" | "OUT_OF_SCOPE" | "UNRESOLVED";

export function resolveStoreAccess(
  access: EffectiveAccess | undefined,
  organizationId: string | undefined,
  permission: StorePermission,
  storeId?: string,
): StoreAccessState {
  if (access === undefined || organizationId === undefined) {
    return "UNRESOLVED";
  }

  if (access.organizationId !== organizationId) {
    return "OUT_OF_SCOPE";
  }

  if (!access.permissions.includes(permission)) {
    return "DENIED";
  }

  if (storeId !== undefined && !can(access, permission, { organizationId, storeId })) {
    return "OUT_OF_SCOPE";
  }

  return "ALLOWED";
}
