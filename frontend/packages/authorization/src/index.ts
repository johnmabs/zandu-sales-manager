"use client";

import { createContext, createElement, useContext } from "react";

import type { AuthAccessScope, AuthEffectiveAccess } from "@zandu/auth";
import type { ReactNode } from "react";

export type PermissionCode = string;

export type AccessScope = AuthAccessScope;

/** A server-provided projection of the current actor's effective access. */
export type EffectiveAccess = AuthEffectiveAccess;

export type CanOptions = Readonly<{
  organizationId?: string;
  storeId?: string;
}>;

export type NavigationCapability = Readonly<{
  permission: PermissionCode;
  storeId?: string;
}>;

export type NavigationItem = Readonly<{
  capabilities: readonly NavigationCapability[];
}>;

export class EffectiveAccessError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "EffectiveAccessError";
  }
}

const EffectiveAccessContext = createContext<EffectiveAccess | undefined>(undefined);

export type EffectiveAccessProviderProps = Readonly<{
  access?: EffectiveAccess;
  children: ReactNode;
}>;

/**
 * The provider accepts only the current server projection. Omitting it keeps
 * consumers denied until session/access bootstrap has completed.
 */
export function EffectiveAccessProvider({ access, children }: EffectiveAccessProviderProps) {
  validateEffectiveAccess(access);

  return createElement(EffectiveAccessContext.Provider, { value: access }, children);
}

export function useEffectiveAccess(): EffectiveAccess | undefined {
  return useContext(EffectiveAccessContext);
}

/** Returns false when access is unresolved or any supplied scope does not match. */
export function useCan(permission: PermissionCode, options?: CanOptions): boolean {
  return can(useEffectiveAccess(), permission, options);
}

export type CanProps = CanOptions &
  Readonly<{
    children: ReactNode;
    fallback?: ReactNode;
    permission: PermissionCode;
  }>;

export function Can({ children, fallback = null, permission, ...options }: CanProps) {
  return useCan(permission, options) ? children : fallback;
}

/**
 * This is an UX guard only. Every route and mutation must still be accepted or
 * denied by the server, which remains the authorization boundary.
 */
export function can(
  access: EffectiveAccess | undefined,
  permission: PermissionCode,
  options?: CanOptions,
): boolean {
  if (access === undefined || !access.permissions.includes(permission)) {
    return false;
  }

  if (options?.organizationId !== undefined && options.organizationId !== access.organizationId) {
    return false;
  }

  if (options?.storeId === undefined) {
    return true;
  }

  if (!access.accessibleStoreIds.includes(options.storeId)) {
    return false;
  }

  return access.scope.type === "ORGANIZATION" || access.scope.storeIds.includes(options.storeId);
}

/** A navigation entry is visible when the actor has at least one relevant permitted capability. */
export function canViewNavigation(
  access: EffectiveAccess | undefined,
  item: NavigationItem,
): boolean {
  return item.capabilities.some(({ permission, storeId }) =>
    can(access, permission, storeId === undefined ? undefined : { storeId }),
  );
}

function validateEffectiveAccess(access: EffectiveAccess | undefined): void {
  if (access === undefined) {
    return;
  }

  assertUnique(access.permissions, "permission");
  assertUnique(access.accessibleStoreIds, "accessible store");

  if (access.scope.type === "SELECTED_STORES") {
    assertUnique(access.scope.storeIds, "scoped store");
    for (const storeId of access.scope.storeIds) {
      if (!access.accessibleStoreIds.includes(storeId)) {
        throw new EffectiveAccessError("A scoped store must be in the accessible store scope.");
      }
    }
  }
}

function assertUnique(values: readonly string[], label: string): void {
  if (new Set(values).size !== values.length) {
    throw new EffectiveAccessError(`The effective access contains a duplicate ${label}.`);
  }
}
