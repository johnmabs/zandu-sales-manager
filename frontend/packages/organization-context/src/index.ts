"use client";

import { createContext, createElement, useContext } from "react";

import type { ReactNode } from "react";

export type OrganizationStatus = "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED";

export type Organization = Readonly<{
  defaultCurrency: string;
  defaultLocale: string;
  defaultTimeZone: string;
  id: string;
  name: string;
  status: OrganizationStatus;
}>;

export type OrganizationContextStatus =
  | "UNKNOWN"
  | "NO_ORGANIZATIONS"
  | "SELECTION_REQUIRED"
  | "ACTIVE";

export type OrganizationContextState = Readonly<{
  activeOrganization?: Organization;
  activeOrganizationId?: string;
  organizations: readonly Organization[];
  status: OrganizationContextStatus;
}>;

type OrganizationContextListener = (state: OrganizationContextState) => void;

export class OrganizationSelectionError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "OrganizationSelectionError";
  }
}

/** Owns an explicit server-validated organization selection; routes are never consulted. */
export class OrganizationContextManager {
  private currentState: OrganizationContextState = { organizations: [], status: "UNKNOWN" };
  private readonly listeners = new Set<OrganizationContextListener>();

  getState(): OrganizationContextState {
    return this.currentState;
  }

  subscribe(listener: OrganizationContextListener): () => void {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }

  setOrganizations(
    organizations: readonly Organization[],
    activeOrganizationId?: string,
  ): OrganizationContextState {
    const normalized = normalizeOrganizations(organizations);
    if (normalized.length === 0) {
      return this.setState({ organizations: [], status: "NO_ORGANIZATIONS" });
    }

    const selectedId = activeOrganizationId ?? this.currentState.activeOrganizationId;
    if (selectedId === undefined) {
      return this.setState({ organizations: normalized, status: "SELECTION_REQUIRED" });
    }

    return this.activate(normalized, selectedId);
  }

  selectOrganization(organizationId: string): OrganizationContextState {
    return this.activate(this.currentState.organizations, organizationId);
  }

  clear(): OrganizationContextState {
    return this.setState({ organizations: [], status: "UNKNOWN" });
  }

  private activate(
    organizations: readonly Organization[],
    organizationId: string,
  ): OrganizationContextState {
    const activeOrganization = organizations.find(
      (organization) => organization.id === organizationId,
    );
    if (activeOrganization === undefined) {
      throw new OrganizationSelectionError("The organization is not in the current server scope.");
    }
    if (activeOrganization.status !== "ACTIVE") {
      throw new OrganizationSelectionError("The organization is not operational.");
    }

    return this.setState({
      activeOrganization,
      activeOrganizationId: activeOrganization.id,
      organizations,
      status: "ACTIVE",
    });
  }

  private setState(state: OrganizationContextState): OrganizationContextState {
    this.currentState = state;
    for (const listener of this.listeners) {
      listener(state);
    }
    return state;
  }
}

const OrganizationReactContext = createContext<OrganizationContextState | undefined>(undefined);

export function OrganizationContextProvider({
  children,
  state,
}: Readonly<{ children: ReactNode; state: OrganizationContextState }>) {
  return createElement(OrganizationReactContext.Provider, { value: state }, children);
}

export function useOrganizationContext(): OrganizationContextState {
  const state = useContext(OrganizationReactContext);
  if (state === undefined) {
    throw new OrganizationSelectionError("OrganizationContextProvider is missing.");
  }
  return state;
}

function normalizeOrganizations(organizations: readonly Organization[]): readonly Organization[] {
  const identifiers = new Set<string>();
  return organizations.map((organization) => {
    if (identifiers.has(organization.id)) {
      throw new OrganizationSelectionError("The organization scope contains a duplicate.");
    }
    identifiers.add(organization.id);
    return { ...organization };
  });
}
