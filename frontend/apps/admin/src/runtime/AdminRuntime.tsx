"use client";

import { ApiClient, FoundationApi, createAuthenticationTransport } from "@zandu/api-client";
import { AuthenticationManager, AuthenticationRequiredError } from "@zandu/auth";
import { publicRuntimeConfig } from "@zandu/config";
import { OrganizationContextManager } from "@zandu/organization-context";
import {
  createServerStateClient,
  queryKeys,
  transitionOrganizationCache,
} from "@zandu/server-state";
import { StoreContextManager } from "@zandu/store-context";
import { createContext, useContext, useEffect, useState } from "react";

import { resolveAdminApiBaseUrl } from "./adminApiBaseUrl";
import { createAdminObservability } from "./adminObservability";

import type { QueryClient } from "@tanstack/react-query";
import type { AuthCredentials, AuthState } from "@zandu/auth";
import type { OrganizationContextState } from "@zandu/organization-context";
import type { StoreContextState } from "@zandu/store-context";
import type { ReactNode } from "react";

type ContextStatus = "IDLE" | "LOADING" | "READY" | "ERROR";

type AdminRuntime = Readonly<{
  api?: FoundationApi;
  authState: AuthState;
  configurationError?: string;
  contextError?: string;
  contextStatus: ContextStatus;
  login: (credentials: AuthCredentials) => Promise<void>;
  logout: () => Promise<void>;
  organizationState: OrganizationContextState;
  queryClient: QueryClient;
  refreshStoreContext: () => Promise<void>;
  selectOrganization: (organizationId: string) => Promise<void>;
  selectStore: (storeId: string) => void;
  storeState?: StoreContextState;
}>;

type RuntimeServices = Readonly<{
  api: FoundationApi;
  auth: AuthenticationManager;
  organizations: OrganizationContextManager;
  queryClient: QueryClient;
}>;

const RuntimeContext = createContext<AdminRuntime | undefined>(undefined);
const fallbackQueryClient = createServerStateClient();

export function AdminRuntimeProvider({ children }: Readonly<{ children: ReactNode }>) {
  const [initialization] = useState(createRuntimeServices);
  const services = initialization.services;
  const [authState, setAuthState] = useState<AuthState>(
    services?.auth.getState() ?? { status: "UNKNOWN" },
  );
  const [organizationState, setOrganizationState] = useState<OrganizationContextState>(
    services?.organizations.getState() ?? { organizations: [], status: "UNKNOWN" },
  );
  const [storeManager, setStoreManager] = useState<StoreContextManager>();
  const [storeState, setStoreState] = useState<StoreContextState>();
  const [contextStatus, setContextStatus] = useState<ContextStatus>("IDLE");
  const [contextError, setContextError] = useState<string>();

  useEffect(() => {
    if (services === undefined) {
      return;
    }
    const unsubscribeAuth = services.auth.subscribe(setAuthState);
    const unsubscribeOrganizations = services.organizations.subscribe(setOrganizationState);
    void services.auth.bootstrap().catch(() => undefined);
    return () => {
      unsubscribeAuth();
      unsubscribeOrganizations();
    };
  }, [services]);

  useEffect(() => {
    const actor = authState.actor;
    if (services === undefined || authState.status !== "AUTHENTICATED" || actor === undefined) {
      if (authState.status === "UNAUTHENTICATED") {
        services?.organizations.clear();
        setStoreManager(undefined);
        setStoreState(undefined);
        setContextStatus("IDLE");
      }
      return;
    }

    let cancelled = false;
    setContextStatus("LOADING");
    setContextError(undefined);

    void (async () => {
      try {
        services.organizations.setOrganizations(actor.organizations, actor.organizationId);
        await transitionOrganizationCache(services.queryClient, actor.organizationId);
        const manager = new StoreContextManager(actor.organizationId);
        const stores = actor.effectiveAccess.permissions.includes("STORE_READ")
          ? await services.api.listAccessibleStores(actor.effectiveAccess)
          : [];
        if (cancelled) {
          return;
        }
        services.queryClient.setQueryData(
          queryKeys.stores.list(actor.organizationId, {
            authorizationVersion: actor.effectiveAccess.authorizationVersion,
          }),
          stores,
        );
        setStoreManager(manager);
        setStoreState(manager.setAccessibleStores(stores));
        setContextStatus("READY");
      } catch {
        if (!cancelled) {
          setContextError("Le contexte de travail n’a pas pu être chargé.");
          setContextStatus("ERROR");
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [authState, services]);

  const runtime: AdminRuntime = {
    ...(services === undefined ? {} : { api: services.api }),
    authState,
    ...(initialization.error === undefined ? {} : { configurationError: initialization.error }),
    ...(contextError === undefined ? {} : { contextError }),
    contextStatus,
    async login(credentials) {
      if (services === undefined) {
        throw new Error(initialization.error ?? "The Admin runtime is unavailable.");
      }
      setContextError(undefined);
      await services.auth.login(credentials);
    },
    async logout() {
      if (services === undefined) {
        return;
      }
      try {
        await services.auth.logout();
      } finally {
        services.queryClient.clear();
        services.organizations.clear();
        setStoreManager(undefined);
        setStoreState(undefined);
      }
    },
    organizationState,
    queryClient: services?.queryClient ?? fallbackQueryClient,
    async refreshStoreContext() {
      const actor = authState.actor;
      if (
        services === undefined ||
        actor === undefined ||
        storeManager === undefined ||
        storeManager.getState().organizationId !== actor.organizationId
      ) {
        return;
      }

      try {
        const stores = actor.effectiveAccess.permissions.includes("STORE_READ")
          ? await services.api.listAccessibleStores(actor.effectiveAccess)
          : [];
        services.queryClient.setQueryData(
          queryKeys.stores.list(actor.organizationId, {
            authorizationVersion: actor.effectiveAccess.authorizationVersion,
          }),
          stores,
        );
        setStoreState(storeManager.setAccessibleStores(stores));
        setContextError(undefined);
        setContextStatus("READY");
      } catch {
        setContextError("Le contexte magasin n’a pas pu être resynchronisé.");
        setContextStatus("ERROR");
      }
    },
    async selectOrganization(organizationId) {
      if (services === undefined) {
        return;
      }
      const state = services.organizations.selectOrganization(organizationId);
      if (state.activeOrganizationId !== undefined) {
        await transitionOrganizationCache(services.queryClient, state.activeOrganizationId);
      }
    },
    selectStore(storeId) {
      if (storeManager !== undefined) {
        setStoreState(storeManager.selectStore(storeId));
      }
    },
    ...(storeState === undefined ? {} : { storeState }),
  };

  return <RuntimeContext.Provider value={runtime}>{children}</RuntimeContext.Provider>;
}

export function useAdminRuntime(): AdminRuntime {
  const runtime = useContext(RuntimeContext);
  if (runtime === undefined) {
    throw new Error("AdminRuntimeProvider is missing.");
  }
  return runtime;
}

function createRuntimeServices(): Readonly<{ error?: string; services?: RuntimeServices }> {
  const apiBaseUrl = resolveAdminApiBaseUrl(
    process.env.NEXT_PUBLIC_API_BASE_URL,
    typeof window === "undefined" ? "http://localhost:3000" : window.location.origin,
  );
  const appEnvironment = process.env.NEXT_PUBLIC_APP_ENV ?? process.env.NODE_ENV;
  try {
    const config = publicRuntimeConfig({ API_BASE_URL: apiBaseUrl, APP_ENV: appEnvironment });
    const observability = createAdminObservability(
      config.appEnvironment,
      process.env.NEXT_PUBLIC_CLIENT_VERSION ?? "0.0.0",
    );
    const sessionReference: { auth: AuthenticationManager | undefined } = { auth: undefined };
    const client = new ApiClient({
      config,
      observability,
      session: {
        getAccessToken: () => sessionReference.auth?.getAccessToken(),
        refreshAndRetry: async (retry) => {
          if (sessionReference.auth === undefined) {
            throw new AuthenticationRequiredError();
          }
          return sessionReference.auth.refreshAndRetry(retry);
        },
      },
    });
    const auth = new AuthenticationManager(createAuthenticationTransport(client));
    sessionReference.auth = auth;
    return {
      services: {
        api: new FoundationApi(client),
        auth,
        organizations: new OrganizationContextManager(),
        queryClient: createServerStateClient(),
      },
    };
  } catch {
    return {
      error:
        "NEXT_PUBLIC_API_BASE_URL doit contenir une URL absolue ou un chemin same-origin commençant par /.",
    };
  }
}
