import { idempotencyHeaders } from "@zandu/idempotency";

import type {
  components as GeneratedComponents,
  operations as GeneratedOperations,
  paths as GeneratedPaths,
} from "./generated/schema";
import type { PublicRuntimeConfig } from "@zandu/config";
import type { ApiError, FieldErrors } from "@zandu/error-contract";
import type { ApiFailureObserver } from "@zandu/observability";

export type OpenApiComponents = GeneratedComponents;
export type OpenApiOperations = GeneratedOperations;
export type OpenApiPaths = GeneratedPaths;
export type SymfonyLoginRequest =
  GeneratedOperations["auth_login"]["requestBody"]["content"]["application/json"];
export type SymfonyAuthenticationResponse =
  GeneratedOperations["auth_login"]["responses"][200]["content"]["application/json"];
export type SymfonyCurrentSessionResponse =
  GeneratedComponents["schemas"]["CurrentSessionResource"];

export const CORRELATION_ID_HEADER = "X-Correlation-ID";

export type ApiRequest = Readonly<{
  accessToken?: string;
  body?: unknown;
  correlationId?: string;
  idempotencyKey?: string;
  method: "DELETE" | "GET" | "PATCH" | "POST" | "PUT";
  path: string;
  requiresAuthentication?: boolean;
  timeoutMs?: number;
}>;

export type ApiResponse = Readonly<{
  correlationId?: string;
  data: unknown;
  status: number;
}>;

export type FetchImplementation = (url: string, init: RequestInit) => Promise<Response>;

/** Structural port implemented by the existing in-memory AuthenticationManager. */
export type SessionCredentials = Readonly<{
  getAccessToken: () => string | undefined;
  refreshAndRetry: <Result>(retry: (accessToken: string) => Promise<Result>) => Promise<Result>;
}>;

export type ApiClientOptions = Readonly<{
  config: PublicRuntimeConfig;
  defaultTimeoutMs?: number;
  fetchImplementation?: FetchImplementation;
  generateCorrelationId?: () => string;
  observability?: ApiFailureObserver;
  session?: SessionCredentials;
}>;

export class ApiRequestError extends Error {
  readonly apiError: ApiError;
  readonly outcomeUnknown: boolean;

  constructor(apiError: ApiError, outcomeUnknown: boolean) {
    super(
      apiError.kind === "network"
        ? (apiError.message ?? "Network request failed.")
        : "API response failed.",
    );
    this.name = "ApiRequestError";
    this.apiError = apiError;
    this.outcomeUnknown = outcomeUnknown;
  }
}

export class ApiClient {
  private readonly config: PublicRuntimeConfig;
  private readonly defaultTimeoutMs: number;
  private readonly fetchImplementation: FetchImplementation;
  private readonly generateCorrelationId: () => string;
  private readonly observability: ApiFailureObserver | undefined;
  private readonly session: SessionCredentials | undefined;

  constructor({
    config,
    defaultTimeoutMs = 15_000,
    fetchImplementation = (url, init) => globalThis.fetch(url, init),
    generateCorrelationId = generateRequestCorrelationId,
    observability,
    session,
  }: ApiClientOptions) {
    if (defaultTimeoutMs <= 0) {
      throw new Error("The default timeout must be positive.");
    }

    this.config = config;
    this.defaultTimeoutMs = defaultTimeoutMs;
    this.fetchImplementation = fetchImplementation;
    this.generateCorrelationId = generateCorrelationId;
    this.observability = observability;
    this.session = session;
  }

  async request(request: ApiRequest): Promise<ApiResponse> {
    const accessToken =
      request.accessToken ??
      (request.requiresAuthentication === false ? undefined : this.session?.getAccessToken());

    try {
      return await this.execute(request, accessToken);
    } catch (error: unknown) {
      if (
        !isUnauthorizedResponse(error) ||
        request.requiresAuthentication === false ||
        request.accessToken !== undefined ||
        this.session === undefined
      ) {
        this.recordFailure(request, error);
        throw error;
      }

      try {
        return await this.session.refreshAndRetry((refreshedAccessToken) =>
          this.execute(request, refreshedAccessToken),
        );
      } catch (retryError: unknown) {
        this.recordFailure(request, retryError);
        throw retryError;
      }
    }
  }

  private async execute(
    request: ApiRequest,
    accessToken: string | undefined,
  ): Promise<ApiResponse> {
    const correlationId = request.correlationId ?? this.generateCorrelationId();
    const controller = new AbortController();
    const timeoutMs = request.timeoutMs ?? this.defaultTimeoutMs;
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const response = await this.fetchImplementation(this.urlFor(request.path), {
        ...(request.body === undefined ? {} : { body: JSON.stringify(request.body) }),
        credentials: "include",
        headers: {
          Accept: "application/json",
          ...(request.body === undefined ? {} : { "Content-Type": "application/json" }),
          ...(accessToken === undefined ? {} : { Authorization: `Bearer ${accessToken}` }),
          [CORRELATION_ID_HEADER]: correlationId,
          ...(request.idempotencyKey === undefined
            ? {}
            : idempotencyHeaders(request.idempotencyKey)),
        },
        method: request.method,
        signal: controller.signal,
      });

      const responseCorrelationId = response.headers.get(CORRELATION_ID_HEADER) ?? undefined;
      if (!response.ok) {
        throw new ApiRequestError(await responseError(response, responseCorrelationId), false);
      }

      return {
        ...(responseCorrelationId === undefined ? {} : { correlationId: responseCorrelationId }),
        data: response.status === 204 ? undefined : await response.json(),
        status: response.status,
      };
    } catch (error: unknown) {
      if (error instanceof ApiRequestError) {
        throw error;
      }

      const timedOut = controller.signal.aborted;
      throw new ApiRequestError(
        {
          ...(timedOut ? { message: "The request timed out." } : {}),
          correlationId,
          kind: "network",
        },
        timedOut && request.idempotencyKey !== undefined,
      );
    } finally {
      clearTimeout(timeout);
    }
  }

  private urlFor(path: string): string {
    return new URL(path, this.config.apiBaseUrl).toString();
  }

  private recordFailure(request: ApiRequest, error: unknown): void {
    if (!(error instanceof ApiRequestError)) {
      return;
    }

    this.observability?.recordApiFailure({
      ...(error.apiError.correlationId === undefined
        ? {}
        : { correlationId: error.apiError.correlationId }),
      failureKind: error.apiError.kind,
      method: request.method,
      outcomeUnknown: error.outcomeUnknown,
      path: request.path,
      ...(error.apiError.kind === "response" ? { status: error.apiError.status } : {}),
    });
  }
}

export type SessionAccessScope =
  | Readonly<{ type: "ORGANIZATION" }>
  | Readonly<{ storeIds: readonly string[]; type: "SELECTED_STORES" }>;

export type CurrentSession = Readonly<{
  authorizationVersion: number;
  email?: string;
  effectiveAccess: Readonly<{
    accessibleStoreIds: readonly string[];
    authorizationVersion: number;
    organizationId: string;
    permissions: readonly string[];
    scope: SessionAccessScope;
  }>;
  id: string;
  organizationId: string;
  organizations: readonly OrganizationResource[];
  userId: string;
}>;

export type AuthenticationTokens = Readonly<{
  accessToken: string;
  refreshExpiresAt?: string;
}>;

export type AuthenticationTransport = Readonly<{
  login: (credentials: Readonly<SymfonyLoginRequest>) => Promise<AuthenticationTokens>;
  logout: () => Promise<void>;
  refresh: () => Promise<AuthenticationTokens>;
  resolveActor: (accessToken: string) => Promise<CurrentSession>;
}>;

/** Adapts Symfony's wire payloads to AuthenticationManager's stable contract. */
export function createAuthenticationTransport(client: ApiClient): AuthenticationTransport {
  return {
    async login(credentials) {
      const response = await client.request({
        body: credentials,
        method: "POST",
        path: "auth/login",
        requiresAuthentication: false,
      });
      return decodeAuthenticationTokens(response.data);
    },
    async logout() {
      await client.request({
        method: "POST",
        path: "auth/logout",
        requiresAuthentication: false,
      });
    },
    async refresh() {
      const response = await client.request({
        method: "POST",
        path: "auth/refresh",
        requiresAuthentication: false,
      });
      return decodeAuthenticationTokens(response.data);
    },
    async resolveActor(accessToken) {
      const response = await client.request({ accessToken, method: "GET", path: "session" });
      return decodeCurrentSession(response.data);
    },
  };
}

export type OrganizationResource = Readonly<{
  defaultCurrency: string;
  defaultLocale: string;
  defaultTimeZone: string;
  id: string;
  name: string;
  status: "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED";
}>;

export type StoreResource = Readonly<{
  address: string | null;
  code: string;
  currency: string;
  id: string;
  locale: string;
  name: string;
  organizationId: string;
  status: "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED";
  timeZone: string;
  updatedAt: string;
  version: number;
}>;

export type StoreCreateInput = Readonly<{
  address: string | null;
  code: string;
  currency: string;
  locale: string;
  name: string;
  timeZone: string;
}>;

export type StoreUpdateInput = Readonly<{
  address: string | null;
  locale: string;
  name: string;
  timeZone: string;
}>;

export type StoreClosureRequestInput = Readonly<{
  reason: string;
}>;

export type StoreClosureStatus = "REQUESTED" | "IN_PROGRESS" | "READY" | "COMPLETED" | "CANCELLED";

export type StoreClosureResource = Readonly<{
  blockers: readonly string[];
  id: string;
  reason: string;
  requestedAt: string;
  status: StoreClosureStatus;
  storeId: string;
  version: number;
}>;

export class FoundationApi {
  private readonly client: ApiClient;

  constructor(client: ApiClient) {
    this.client = client;
  }

  async getOrganization(organizationId: string): Promise<OrganizationResource> {
    const response = await this.client.request({
      method: "GET",
      path: `organizations/${encodeURIComponent(organizationId)}`,
    });
    return decodeOrganization(response.data);
  }

  async listAccessibleStores(
    access: CurrentSession["effectiveAccess"],
  ): Promise<readonly StoreResource[]> {
    const response = await this.client.request({ method: "GET", path: "stores" });
    const stores = decodeStores(response.data);
    const accessible = new Set(access.accessibleStoreIds);
    return stores.filter(
      (store) => store.organizationId === access.organizationId && accessible.has(store.id),
    );
  }

  async getAccessibleStore(
    storeId: string,
    access: CurrentSession["effectiveAccess"],
  ): Promise<StoreResource | undefined> {
    const response = await this.client.request({
      method: "GET",
      path: `stores/${encodeURIComponent(storeId)}`,
    });
    const store = decodeStore(response.data);

    return store.organizationId === access.organizationId &&
      access.accessibleStoreIds.includes(store.id)
      ? store
      : undefined;
  }

  async createStore(input: StoreCreateInput): Promise<StoreResource> {
    const response = await this.client.request({ body: input, method: "POST", path: "stores" });

    return decodeStore(response.data);
  }

  async updateStore(storeId: string, input: StoreUpdateInput): Promise<StoreResource> {
    const response = await this.client.request({
      body: input,
      method: "PATCH",
      path: `stores/${encodeURIComponent(storeId)}`,
    });

    return decodeStore(response.data);
  }

  async suspendStore(storeId: string): Promise<StoreResource> {
    const response = await this.client.request({
      method: "POST",
      path: `stores/${encodeURIComponent(storeId)}/suspend`,
    });

    return decodeStore(response.data);
  }

  async requestStoreClosure(
    storeId: string,
    input: StoreClosureRequestInput,
  ): Promise<StoreClosureResource> {
    const response = await this.client.request({
      body: input,
      method: "POST",
      path: `stores/${encodeURIComponent(storeId)}/closure-request`,
    });

    return decodeStoreClosure(response.data);
  }

  async cancelStoreClosure(storeId: string): Promise<StoreResource> {
    const response = await this.client.request({
      method: "POST",
      path: `stores/${encodeURIComponent(storeId)}/closure-request/cancel`,
    });

    return decodeStore(response.data);
  }
}

function decodeAuthenticationTokens(value: unknown): AuthenticationTokens {
  if (!isRecord(value) || typeof value.token !== "string") {
    throw new ApiContractError("The authentication response is invalid.");
  }
  if (value.refreshExpiresAt !== undefined && typeof value.refreshExpiresAt !== "string") {
    throw new ApiContractError("The refresh expiry is invalid.");
  }
  return {
    accessToken: value.token,
    ...(value.refreshExpiresAt === undefined ? {} : { refreshExpiresAt: value.refreshExpiresAt }),
  };
}

function decodeCurrentSession(value: unknown): CurrentSession {
  if (
    !isRecord(value) ||
    !isEffectiveAccess(value.effectiveAccess) ||
    !Array.isArray(value.organizations)
  ) {
    throw new ApiContractError("The current session response is invalid.");
  }
  if (typeof value.authorizationVersion !== "number") {
    throw new ApiContractError("The authorization version is invalid.");
  }
  const organizations = value.organizations.map(decodeOrganization);
  const organizationId = requiredString(
    value,
    "organizationId",
    "The current actor response is invalid.",
  );
  if (
    value.effectiveAccess.organizationId !== organizationId ||
    value.effectiveAccess.authorizationVersion !== value.authorizationVersion ||
    !organizations.some((organization) => organization.id === organizationId)
  ) {
    throw new ApiContractError("The current session context is inconsistent.");
  }
  return {
    authorizationVersion: value.authorizationVersion,
    ...(typeof value.email === "string" ? { email: value.email } : {}),
    effectiveAccess: value.effectiveAccess,
    id: requiredString(value, "id", "The current actor response is invalid."),
    organizationId,
    organizations,
    userId: requiredString(value, "userId", "The current actor response is invalid."),
  };
}

function isEffectiveAccess(value: unknown): value is CurrentSession["effectiveAccess"] {
  if (
    !isRecord(value) ||
    typeof value.organizationId !== "string" ||
    typeof value.authorizationVersion !== "number" ||
    !isStringArray(value.permissions) ||
    !isStringArray(value.accessibleStoreIds) ||
    !isRecord(value.scope)
  ) {
    return false;
  }
  return (
    value.scope.type === "ORGANIZATION" ||
    (value.scope.type === "SELECTED_STORES" && isStringArray(value.scope.storeIds))
  );
}

function decodeOrganization(value: unknown): OrganizationResource {
  if (!isRecord(value)) {
    throw new ApiContractError("The organization response is invalid.");
  }
  if (!isOperationalStatus(value.status)) {
    throw new ApiContractError("The organization response is invalid.");
  }
  return {
    defaultCurrency: requiredString(
      value,
      "defaultCurrency",
      "The organization response is invalid.",
    ),
    defaultLocale: requiredString(value, "defaultLocale", "The organization response is invalid."),
    defaultTimeZone: requiredString(
      value,
      "defaultTimeZone",
      "The organization response is invalid.",
    ),
    id: requiredString(value, "id", "The organization response is invalid."),
    name: requiredString(value, "name", "The organization response is invalid."),
    status: value.status,
  };
}

function decodeStores(value: unknown): readonly StoreResource[] {
  const items = Array.isArray(value)
    ? value
    : isRecord(value) && Array.isArray(value.member)
      ? value.member
      : undefined;
  if (items === undefined) {
    throw new ApiContractError("The store collection response is invalid.");
  }
  return items.map(decodeStore);
}

function decodeStore(value: unknown): StoreResource {
  if (!isRecord(value) || !isOperationalStatus(value.status)) {
    throw new ApiContractError("A store response is invalid.");
  }
  if (value.address !== null && typeof value.address !== "string") {
    throw new ApiContractError("A store response is invalid.");
  }

  return {
    address: value.address,
    code: requiredString(value, "code", "A store response is invalid."),
    currency: requiredString(value, "currency", "A store response is invalid."),
    id: requiredString(value, "id", "A store response is invalid."),
    locale: requiredString(value, "locale", "A store response is invalid."),
    name: requiredString(value, "name", "A store response is invalid."),
    organizationId: requiredString(value, "organizationId", "A store response is invalid."),
    status: value.status,
    timeZone: requiredString(value, "timeZone", "A store response is invalid."),
    updatedAt: requiredString(value, "updatedAt", "A store response is invalid."),
    version: requiredNumber(value, "version", "A store response is invalid."),
  };
}

function decodeStoreClosure(value: unknown): StoreClosureResource {
  if (!isRecord(value) || !isStoreClosureStatus(value.status) || !isStringArray(value.blockers)) {
    throw new ApiContractError("A store closure response is invalid.");
  }

  return {
    blockers: value.blockers,
    id: requiredString(value, "id", "A store closure response is invalid."),
    reason: requiredString(value, "reason", "A store closure response is invalid."),
    requestedAt: requiredString(value, "requestedAt", "A store closure response is invalid."),
    status: value.status,
    storeId: requiredString(value, "storeId", "A store closure response is invalid."),
    version: requiredNumber(value, "version", "A store closure response is invalid."),
  };
}

export class ApiContractError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "ApiContractError";
  }
}

function isStringArray(value: unknown): value is readonly string[] {
  return Array.isArray(value) && value.every((item) => typeof item === "string");
}

function requiredString(value: Record<string, unknown>, property: string, message: string): string {
  const propertyValue = value[property];
  if (typeof propertyValue !== "string") {
    throw new ApiContractError(message);
  }
  return propertyValue;
}

function requiredNumber(value: Record<string, unknown>, property: string, message: string): number {
  const propertyValue = value[property];
  if (typeof propertyValue !== "number") {
    throw new ApiContractError(message);
  }
  return propertyValue;
}

function isOperationalStatus(
  value: unknown,
): value is "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED" {
  return (
    value === "ACTIVE" || value === "SUSPENDED" || value === "CLOSURE_PENDING" || value === "CLOSED"
  );
}

function isStoreClosureStatus(value: unknown): value is StoreClosureStatus {
  return (
    value === "REQUESTED" ||
    value === "IN_PROGRESS" ||
    value === "READY" ||
    value === "COMPLETED" ||
    value === "CANCELLED"
  );
}

function generateRequestCorrelationId(): string {
  if (typeof globalThis.crypto?.randomUUID !== "function") {
    throw new Error("The runtime cannot generate a correlation id.");
  }

  return globalThis.crypto.randomUUID();
}

async function responseError(
  response: Response,
  correlationId: string | undefined,
): Promise<ApiError> {
  const payload = await response.json().catch((): undefined => undefined);
  const decoded = isErrorPayload(payload) ? payload : undefined;
  const effectiveCorrelationId = decoded?.correlationId ?? correlationId;

  return {
    ...(decoded?.code === undefined ? {} : { code: decoded.code }),
    ...(effectiveCorrelationId === undefined ? {} : { correlationId: effectiveCorrelationId }),
    ...(decoded?.fieldErrors === undefined ? {} : { fieldErrors: decoded.fieldErrors }),
    kind: "response",
    ...(decoded?.message === undefined ? {} : { message: decoded.message }),
    status: response.status,
  };
}

function isErrorPayload(value: unknown): value is Readonly<{
  code?: string;
  correlationId?: string;
  fieldErrors?: FieldErrors;
  message?: string;
}> {
  if (!isRecord(value)) {
    return false;
  }

  const payload = value;
  return (
    (payload.code === undefined || typeof payload.code === "string") &&
    (payload.correlationId === undefined || typeof payload.correlationId === "string") &&
    (payload.message === undefined || typeof payload.message === "string") &&
    (payload.fieldErrors === undefined || isFieldErrors(payload.fieldErrors))
  );
}

function isFieldErrors(value: unknown): value is FieldErrors {
  if (!isRecord(value)) {
    return false;
  }

  return Object.values(value).every(
    (messages) =>
      Array.isArray(messages) && messages.every((message) => typeof message === "string"),
  );
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null;
}

function isUnauthorizedResponse(error: unknown): error is ApiRequestError {
  return (
    error instanceof ApiRequestError &&
    error.apiError.kind === "response" &&
    error.apiError.status === 401
  );
}
