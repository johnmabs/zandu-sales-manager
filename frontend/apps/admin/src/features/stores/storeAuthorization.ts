import { can } from "@zandu/authorization";

import type { StoreResource } from "@zandu/api-client";
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
export type StoreActionAvailability = Readonly<{
  cancelClosure: boolean;
  edit: boolean;
  reactivate: boolean;
  requestClosure: boolean;
  suspend: boolean;
}>;

export function availableStoreActions({
  access,
  organizationId,
  status,
  storeId,
}: Readonly<{
  access: EffectiveAccess | undefined;
  organizationId: string | undefined;
  status: StoreResource["status"] | undefined;
  storeId: string;
}>): StoreActionAvailability {
  const allowed = (permission: StorePermission) =>
    resolveStoreAccess(access, organizationId, permission, storeId) === "ALLOWED";
  const canClose = allowed(storePermissions.close);
  const canSuspend = allowed(storePermissions.suspend);

  return {
    cancelClosure: status === "CLOSURE_PENDING" && canClose,
    edit: status === "ACTIVE" && allowed(storePermissions.update),
    reactivate: status === "SUSPENDED" && canSuspend,
    requestClosure: (status === "ACTIVE" || status === "SUSPENDED") && canClose,
    suspend: status === "ACTIVE" && canSuspend,
  };
}

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
