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
  telemetry?: Readonly<{
    feature: string;
    operation: string;
    route: string;
  }>;
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
      ...(error.apiError.kind !== "response" || error.apiError.code === undefined
        ? {}
        : { category: error.apiError.code }),
      ...(error.apiError.correlationId === undefined
        ? {}
        : { correlationId: error.apiError.correlationId }),
      failureKind: error.apiError.kind,
      ...(request.telemetry === undefined ? {} : request.telemetry),
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

export type MembershipStatus = "ACTIVE" | "INVITED" | "SUSPENDED" | "REVOKED";

/** Server-published role assignment summary; role labels remain a later catalog concern. */
export type MembershipRoleAssignment = Readonly<{
  assignmentId: string;
  expiresAt: string | null;
  roleId: string;
  scopeType: string;
  storeIds: readonly string[];
}>;

export type MembershipResource = Readonly<{
  authorizationVersion: number;
  createdAt: string;
  id: string;
  organizationId: string;
  roleAssignments: readonly MembershipRoleAssignment[];
  status: MembershipStatus;
  updatedAt: string;
  userId: string;
  version: number;
}>;

export type RoleAssignmentCreateInput = Readonly<{
  expiresAt: string | null;
  roleId: string;
  scopeType: "ORGANIZATION" | "SELECTED_STORES";
  storeIds: readonly string[];
}>;

export type RoleStatus = "ACTIVE" | "ARCHIVED";
export type RoleType = "CUSTOM" | "SYSTEM";

export type RoleResource = Readonly<{
  code: string;
  description: string | null;
  id: string;
  name: string;
  permissions: readonly string[];
  status: RoleStatus;
  type: RoleType;
  version: number;
}>;

export type InvitationCreateInput = Readonly<{
  email: string;
  expiresAt: string | null;
  roleAssignments: readonly Readonly<{ roleCode: string; storeIds: readonly string[] }>[];
}>;

export type InvitationResource = Readonly<{
  acceptedAt: string | null;
  email: string;
  expiresAt: string;
  id: string;
  organizationId: string;
  roleAssignments: readonly Readonly<{ roleCode: string; storeIds: readonly string[] }>[];
  status: string;
  version: number;
}>;

/** The invitation token is returned only by the create operation. Do not persist it. */
export type CreatedInvitationResource = Readonly<{
  invitation: InvitationResource;
  token: string;
}>;

export type ProductStatus = "ACTIVE" | "ARCHIVED" | "DRAFT" | "INACTIVE";
export type ProductType = "PHYSICAL" | "SERVICE";
export type CategoryStatus = "ACTIVE" | "ARCHIVED" | "INACTIVE";
export type ProductPackagingStatus = CategoryStatus;
export type ProductBarcodeStatus = "ACTIVE" | "REMOVED";
export type ProductResource = Readonly<
  Omit<Required<GeneratedComponents["schemas"]["ProductResource"]>, "status" | "type"> & {
    status: ProductStatus;
    type: ProductType;
  }
>;
export type CategoryResource = Readonly<
  Omit<Required<GeneratedComponents["schemas"]["CategoryResource"]>, "status"> & {
    status: CategoryStatus;
  }
>;
export type ProductPackagingResource = Readonly<
  Omit<Required<GeneratedComponents["schemas"]["ProductPackagingResource"]>, "status"> & {
    status: ProductPackagingStatus;
  }
>;
export type ProductBarcodeResource = Readonly<
  Omit<Required<GeneratedComponents["schemas"]["ProductBarcodeResource"]>, "status"> & {
    status: ProductBarcodeStatus;
  }
>;

export type PriceListStatus = "ACTIVE" | "ARCHIVED" | "DRAFT" | "INACTIVE";
export type PriceListScope = "ORGANIZATION";
export type ProductPriceStatus = "ACTIVE" | "ARCHIVED" | "INACTIVE";
export type PriceListResource = Readonly<
  Omit<Required<GeneratedComponents["schemas"]["PriceListResource"]>, "scope" | "status"> & {
    scope: PriceListScope;
    status: PriceListStatus;
  }
>;
export type ProductPriceResource = Readonly<
  Omit<Required<GeneratedComponents["schemas"]["ProductPriceResource"]>, "status"> & {
    status: ProductPriceStatus;
  }
>;
export type EffectiveProductPriceResource = Readonly<
  Required<GeneratedComponents["schemas"]["EffectiveProductPriceResource"]>
>;
export type PriceListCreateInput = Readonly<
  GeneratedComponents["schemas"]["PriceListResource.PriceListCreateInput"]
>;
export type PriceListUpdateInput = Readonly<
  GeneratedComponents["schemas"]["PriceListResource.PriceListUpdateInput.jsonMergePatch"]
>;
export type ProductPriceCreateInput = Readonly<
  GeneratedComponents["schemas"]["ProductPriceResource.ProductPriceCreateInput"]
>;
export type ProductPriceUpdateInput = Readonly<
  GeneratedComponents["schemas"]["ProductPriceResource.ProductPriceUpdateInput.jsonMergePatch"]
>;

export type ProductFilters = Readonly<{
  status?: string;
  type?: string;
  categoryId?: string;
  productCode?: string;
  search?: string;
}>;
export type ProductCreateInput = Readonly<
  GeneratedComponents["schemas"]["ProductResource.ProductCreateInput"]
>;
export type ProductUpdateInput = Readonly<
  GeneratedComponents["schemas"]["ProductResource.ProductUpdateInput.jsonMergePatch"]
>;
export type CategoryCreateInput = Readonly<
  GeneratedComponents["schemas"]["CategoryResource.CategoryCreateInput"]
>;
export type CategoryUpdateInput = Readonly<
  GeneratedComponents["schemas"]["CategoryResource.CategoryUpdateInput.jsonMergePatch"]
>;
export type PackagingCreateInput = Readonly<
  GeneratedComponents["schemas"]["ProductPackagingResource.ProductPackagingCreateInput"]
>;
export type PackagingUpdateInput = Readonly<
  GeneratedComponents["schemas"]["ProductPackagingResource.ProductPackagingUpdateInput.jsonMergePatch"]
>;

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
    const response = await this.client.request({
      method: "GET",
      path: "stores",
      telemetry: storeTelemetry("list", "/admin/stores"),
    });
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
      telemetry: storeTelemetry("details", "/admin/stores/:id"),
    });
    const store = decodeStore(response.data);

    return store.organizationId === access.organizationId &&
      access.accessibleStoreIds.includes(store.id)
      ? store
      : undefined;
  }

  async createStore(input: StoreCreateInput): Promise<StoreResource> {
    const response = await this.client.request({
      body: input,
      method: "POST",
      path: "stores",
      telemetry: storeTelemetry("create", "/admin/stores/new"),
    });

    return decodeStore(response.data);
  }

  async updateStore(storeId: string, input: StoreUpdateInput): Promise<StoreResource> {
    const response = await this.client.request({
      body: input,
      method: "PATCH",
      path: `stores/${encodeURIComponent(storeId)}`,
      telemetry: storeTelemetry("update", "/admin/stores/:id/edit"),
    });

    return decodeStore(response.data);
  }

  async suspendStore(storeId: string): Promise<StoreResource> {
    const response = await this.client.request({
      method: "POST",
      path: `stores/${encodeURIComponent(storeId)}/suspend`,
      telemetry: storeTelemetry("suspend", "/admin/stores/:id"),
    });

    return decodeStore(response.data);
  }

  async reactivateStore(storeId: string): Promise<StoreResource> {
    const response = await this.client.request({
      method: "POST",
      path: `stores/${encodeURIComponent(storeId)}/reactivate`,
      telemetry: storeTelemetry("reactivate", "/admin/stores/:id"),
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
      telemetry: storeTelemetry("request_closure", "/admin/stores/:id"),
    });

    return decodeStoreClosure(response.data);
  }

  async cancelStoreClosure(storeId: string): Promise<StoreResource> {
    const response = await this.client.request({
      method: "POST",
      path: `stores/${encodeURIComponent(storeId)}/closure-request/cancel`,
      telemetry: storeTelemetry("cancel_closure", "/admin/stores/:id"),
    });

    return decodeStore(response.data);
  }

  async listPriceLists(
    access: CurrentSession["effectiveAccess"],
  ): Promise<readonly PriceListResource[]> {
    const response = await this.client.request({
      method: "GET",
      path: "price-lists",
      telemetry: {
        feature: "pricing",
        operation: "list_price_lists",
        route: "/admin/pricing/price-lists",
      },
    });
    return decodeCatalogCollection(response.data, decodePriceList).filter(
      (item) => item.organizationId === access.organizationId,
    );
  }

  async listProductPrices(
    access: CurrentSession["effectiveAccess"],
  ): Promise<readonly ProductPriceResource[]> {
    const response = await this.client.request({
      method: "GET",
      path: "product-prices",
      telemetry: {
        feature: "pricing",
        operation: "list_product_prices",
        route: "/admin/pricing/product-prices",
      },
    });
    return decodeCatalogCollection(response.data, decodeProductPrice).filter(
      (item) => item.organizationId === access.organizationId,
    );
  }

  async getEffectiveProductPrice(
    productId: string,
    packagingId: string,
    at?: string,
  ): Promise<EffectiveProductPriceResource> {
    const query = at === undefined ? "" : `?${new URLSearchParams({ at })}`;
    const response = await this.client.request({
      method: "GET",
      path: `products/${encodeURIComponent(productId)}/packagings/${encodeURIComponent(packagingId)}/effective-price${query}`,
      telemetry: {
        feature: "pricing",
        operation: "effective_price",
        route: "/admin/pricing/product-prices",
      },
    });
    return decodeEffectiveProductPrice(response.data);
  }

  async listProducts(
    access: CurrentSession["effectiveAccess"],
    filters: ProductFilters = {},
  ): Promise<readonly ProductResource[]> {
    const query = new URLSearchParams();
    for (const key of ["status", "type", "categoryId", "productCode", "search"] as const) {
      const value = filters[key];
      if (value !== undefined && value !== "") query.set(key, value);
    }
    const response = await this.client.request({
      method: "GET",
      path: `products${query.size === 0 ? "" : `?${query}`}`,
      telemetry: {
        feature: "catalog",
        operation: "list_products",
        route: "/admin/catalog/products",
      },
    });
    return decodeCatalogCollection(response.data, decodeProduct).filter(
      (product) => product.organizationId === access.organizationId,
    );
  }

  async listCategories(
    access: CurrentSession["effectiveAccess"],
  ): Promise<readonly CategoryResource[]> {
    const response = await this.client.request({
      method: "GET",
      path: "categories",
      telemetry: {
        feature: "catalog",
        operation: "list_categories",
        route: "/admin/catalog/categories",
      },
    });
    return decodeCatalogCollection(response.data, decodeCategory).filter(
      (category) => category.organizationId === access.organizationId,
    );
  }

  async getProduct(
    productId: string,
    access: CurrentSession["effectiveAccess"],
  ): Promise<ProductResource | undefined> {
    const response = await this.client.request({
      method: "GET",
      path: `products/${encodeURIComponent(productId)}`,
    });
    const product = decodeProduct(response.data);
    return product.organizationId === access.organizationId ? product : undefined;
  }

  async createProduct(input: ProductCreateInput): Promise<ProductResource> {
    const response = await this.client.request({ body: input, method: "POST", path: "products" });
    return decodeProduct(response.data);
  }

  async updateProduct(productId: string, input: ProductUpdateInput): Promise<ProductResource> {
    const response = await this.client.request({
      body: input,
      method: "PATCH",
      path: `products/${encodeURIComponent(productId)}`,
    });
    return decodeProduct(response.data);
  }

  async transitionProduct(
    productId: string,
    transition: "activate" | "archive" | "deactivate" | "reactivate",
  ): Promise<ProductResource> {
    const response = await this.client.request({
      method: "POST",
      path: `products/${encodeURIComponent(productId)}/${transition}`,
    });
    return decodeProduct(response.data);
  }

  async listProductPackagings(
    productId: string,
    access: CurrentSession["effectiveAccess"],
  ): Promise<readonly ProductPackagingResource[]> {
    const response = await this.client.request({
      method: "GET",
      path: `products/${encodeURIComponent(productId)}/packagings`,
    });
    return decodeCatalogCollection(response.data, decodeProductPackaging).filter(
      (item) => item.organizationId === access.organizationId && item.productId === productId,
    );
  }

  async createProductPackaging(
    productId: string,
    input: PackagingCreateInput,
  ): Promise<ProductPackagingResource> {
    const response = await this.client.request({
      body: input,
      method: "POST",
      path: `products/${encodeURIComponent(productId)}/packagings`,
    });
    return decodeProductPackaging(response.data);
  }

  async updateProductPackaging(
    productId: string,
    packagingId: string,
    input: PackagingUpdateInput,
  ): Promise<ProductPackagingResource> {
    const response = await this.client.request({
      body: input,
      method: "PATCH",
      path: `products/${encodeURIComponent(productId)}/packagings/${encodeURIComponent(packagingId)}`,
    });
    return decodeProductPackaging(response.data);
  }

  async transitionProductPackaging(
    productId: string,
    packagingId: string,
    transition: "archive" | "deactivate",
  ): Promise<ProductPackagingResource> {
    const response = await this.client.request({
      method: "POST",
      path: `products/${encodeURIComponent(productId)}/packagings/${encodeURIComponent(packagingId)}/${transition}`,
    });
    return decodeProductPackaging(response.data);
  }

  async createCategory(input: CategoryCreateInput): Promise<CategoryResource> {
    const response = await this.client.request({ body: input, method: "POST", path: "categories" });
    return decodeCategory(response.data);
  }

  async updateCategory(categoryId: string, input: CategoryUpdateInput): Promise<CategoryResource> {
    const response = await this.client.request({
      body: input,
      method: "PATCH",
      path: `categories/${encodeURIComponent(categoryId)}`,
    });
    return decodeCategory(response.data);
  }

  async moveCategory(
    categoryId: string,
    parentCategoryId: string | null,
  ): Promise<CategoryResource> {
    const response = await this.client.request({
      body: { parentCategoryId },
      method: "POST",
      path: `categories/${encodeURIComponent(categoryId)}/move`,
    });
    return decodeCategory(response.data);
  }

  async transitionCategory(
    categoryId: string,
    transition: "activate" | "archive" | "deactivate",
  ): Promise<CategoryResource> {
    const response = await this.client.request({
      method: "POST",
      path: `categories/${encodeURIComponent(categoryId)}/${transition}`,
    });
    return decodeCategory(response.data);
  }

  async listOrganizationMembers(
    access: CurrentSession["effectiveAccess"],
  ): Promise<readonly MembershipResource[]> {
    const response = await this.client.request({
      method: "GET",
      path: "members",
      telemetry: memberTelemetry("list", "/admin/access/members"),
    });

    return decodeMemberships(response.data).filter(
      (membership) => membership.organizationId === access.organizationId,
    );
  }

  async getOrganizationMember(
    membershipId: string,
    access: CurrentSession["effectiveAccess"],
  ): Promise<MembershipResource | undefined> {
    const response = await this.client.request({
      method: "GET",
      path: `members/${encodeURIComponent(membershipId)}`,
      telemetry: memberTelemetry("details", "/admin/access/members/:id"),
    });
    const membership = decodeMembership(response.data);

    return membership.organizationId === access.organizationId ? membership : undefined;
  }

  async assignMemberRole(
    membershipId: string,
    input: RoleAssignmentCreateInput,
  ): Promise<MembershipResource> {
    const response = await this.client.request({
      body: input,
      method: "POST",
      path: `members/${encodeURIComponent(membershipId)}/role-assignments`,
      telemetry: memberTelemetry("assign_role", "/admin/access/members/:id"),
    });
    return decodeMembership(response.data);
  }

  async removeMemberRole(membershipId: string, assignmentId: string): Promise<MembershipResource> {
    const response = await this.client.request({
      method: "DELETE",
      path: `members/${encodeURIComponent(membershipId)}/role-assignments/${encodeURIComponent(assignmentId)}`,
      telemetry: memberTelemetry("remove_role", "/admin/access/members/:id"),
    });
    return decodeMembership(response.data);
  }

  async suspendMember(membershipId: string): Promise<MembershipResource> {
    const response = await this.client.request({
      method: "POST",
      path: `members/${encodeURIComponent(membershipId)}/suspend`,
      telemetry: memberTelemetry("suspend", "/admin/access/members/:id"),
    });
    return decodeMembership(response.data);
  }

  async reactivateMember(membershipId: string): Promise<MembershipResource> {
    const response = await this.client.request({
      method: "POST",
      path: `members/${encodeURIComponent(membershipId)}/reactivate`,
      telemetry: memberTelemetry("reactivate", "/admin/access/members/:id"),
    });
    return decodeMembership(response.data);
  }

  async revokeMember(membershipId: string): Promise<MembershipResource> {
    const response = await this.client.request({
      method: "POST",
      path: `members/${encodeURIComponent(membershipId)}/revoke`,
      telemetry: memberTelemetry("revoke", "/admin/access/members/:id"),
    });
    return decodeMembership(response.data);
  }

  async listRoles(): Promise<readonly RoleResource[]> {
    const response = await this.client.request({
      method: "GET",
      path: "roles",
      telemetry: roleTelemetry("list", "/admin/access/roles"),
    });

    return decodeRoles(response.data);
  }

  async inviteMember(input: InvitationCreateInput): Promise<CreatedInvitationResource> {
    const response = await this.client.request({
      body: input,
      method: "POST",
      path: "member-invitations",
      telemetry: { feature: "access", operation: "invite", route: "/admin/access/invite" },
    });

    return decodeCreatedInvitation(response.data);
  }

  async cancelInvitation(invitationId: string): Promise<InvitationResource> {
    const response = await this.client.request({
      method: "POST",
      path: `member-invitations/${encodeURIComponent(invitationId)}/cancel`,
      telemetry: {
        feature: "access",
        operation: "cancel_invitation",
        route: "/admin/access/invite",
      },
    });

    return decodeInvitation(response.data);
  }
}

function storeTelemetry(operation: string, route: string) {
  return { feature: "stores", operation, route } as const;
}

function memberTelemetry(operation: string, route: string) {
  return { feature: "access", operation, route } as const;
}

function roleTelemetry(operation: string, route: string) {
  return { feature: "access", operation, route } as const;
}

function decodeCatalogCollection<Resource>(
  value: unknown,
  decode: (value: unknown) => Resource,
): readonly Resource[] {
  const items = Array.isArray(value)
    ? value
    : isRecord(value) && Array.isArray(value.member)
      ? value.member
      : undefined;
  if (items === undefined)
    throw new ApiContractError("The catalog collection response is invalid.");
  return items.map(decode);
}

function catalogNullableString(value: Record<string, unknown>, key: string): string | null {
  if (value[key] === null) return null;
  return requiredString(value, key, "The catalog response is invalid.");
}

function decodePriceList(value: unknown): PriceListResource {
  const message = "The pricing response is invalid.";
  if (!isRecord(value)) throw new ApiContractError(message);
  return {
    id: requiredString(value, "id", message),
    organizationId: requiredString(value, "organizationId", message),
    code: requiredString(value, "code", message),
    name: requiredString(value, "name", message),
    currency: requiredString(value, "currency", message),
    status: requiredPriceListStatus(value, "status", message),
    scope: requiredPriceListScope(value, "scope", message),
    createdAt: requiredString(value, "createdAt", message),
    validFrom: catalogNullableString(value, "validFrom"),
    validTo: catalogNullableString(value, "validTo"),
    priority: requiredNumber(value, "priority", message),
    version: requiredNumber(value, "version", message),
  };
}

function decodeProductPrice(value: unknown): ProductPriceResource {
  const message = "The pricing response is invalid.";
  if (!isRecord(value)) throw new ApiContractError(message);
  return {
    id: requiredString(value, "id", message),
    organizationId: requiredString(value, "organizationId", message),
    priceListId: requiredString(value, "priceListId", message),
    productId: requiredString(value, "productId", message),
    packagingId: requiredString(value, "packagingId", message),
    amount: requiredString(value, "amount", message),
    currency: requiredString(value, "currency", message),
    status: requiredProductPriceStatus(value, "status", message),
    createdAt: requiredString(value, "createdAt", message),
    validFrom: catalogNullableString(value, "validFrom"),
    validTo: catalogNullableString(value, "validTo"),
    version: requiredNumber(value, "version", message),
  };
}

function decodeEffectiveProductPrice(value: unknown): EffectiveProductPriceResource {
  const message = "The pricing response is invalid.";
  if (!isRecord(value)) throw new ApiContractError(message);
  return {
    priceListId: requiredString(value, "priceListId", message),
    productPriceId: requiredString(value, "productPriceId", message),
    amount: requiredString(value, "amount", message),
    currency: requiredString(value, "currency", message),

    sourceVersion: requiredNumber(value, "sourceVersion", message),
  };
}

function decodeProduct(value: unknown): ProductResource {
  if (!isRecord(value) || typeof value.inventoryTracked !== "boolean")
    throw new ApiContractError("The product response is invalid.");
  const message = "The product response is invalid.";
  return {
    id: requiredString(value, "id", message),
    organizationId: requiredString(value, "organizationId", message),
    productCode: requiredString(value, "productCode", message),
    name: requiredString(value, "name", message),
    status: requiredProductStatus(value, "status", message),
    type: requiredProductType(value, "type", message),
    baseUnitId: requiredString(value, "baseUnitId", message),
    createdAt: requiredString(value, "createdAt", message),
    description: catalogNullableString(value, "description"),
    taxCategoryId: catalogNullableString(value, "taxCategoryId"),
    categoryId: catalogNullableString(value, "categoryId"),
    activatedAt: catalogNullableString(value, "activatedAt"),
    updatedAt: catalogNullableString(value, "updatedAt"),
    inventoryTracked: value.inventoryTracked,
    version: requiredNumber(value, "version", message),
  };
}

function decodeCategory(value: unknown): CategoryResource {
  if (!isRecord(value)) throw new ApiContractError("The category response is invalid.");
  const message = "The category response is invalid.";
  return {
    id: requiredString(value, "id", message),
    organizationId: requiredString(value, "organizationId", message),
    name: requiredString(value, "name", message),
    status: requiredCategoryStatus(value, "status", message),
    createdAt: requiredString(value, "createdAt", message),
    parentCategoryId: catalogNullableString(value, "parentCategoryId"),
    updatedAt: catalogNullableString(value, "updatedAt"),
    version: requiredNumber(value, "version", message),
  };
}

function decodeProductPackaging(value: unknown): ProductPackagingResource {
  const message = "The product packaging response is invalid.";
  if (
    !isRecord(value) ||
    typeof value.base !== "boolean" ||
    typeof value.allowedForSale !== "boolean" ||
    typeof value.allowedForPurchase !== "boolean"
  )
    throw new ApiContractError(message);
  return {
    id: requiredString(value, "id", message),
    organizationId: requiredString(value, "organizationId", message),
    productId: requiredString(value, "productId", message),
    base: value.base,
    code: requiredString(value, "code", message),
    name: requiredString(value, "name", message),
    unitId: requiredString(value, "unitId", message),
    conversionFactor: requiredString(value, "conversionFactor", message),
    precision: requiredNumber(value, "precision", message),
    minimumQuantity: requiredString(value, "minimumQuantity", message),
    quantityIncrement: requiredString(value, "quantityIncrement", message),
    allowedForSale: value.allowedForSale,
    allowedForPurchase: value.allowedForPurchase,
    status: requiredCategoryStatus(value, "status", message),
    createdAt: requiredString(value, "createdAt", message),
    updatedAt: catalogNullableString(value, "updatedAt"),
    version: requiredNumber(value, "version", message),
  };
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

function decodeMemberships(value: unknown): readonly MembershipResource[] {
  const items = Array.isArray(value)
    ? value
    : isRecord(value) && Array.isArray(value.member)
      ? value.member
      : undefined;
  if (items === undefined) {
    throw new ApiContractError("The member collection response is invalid.");
  }

  return items.map(decodeMembership);
}

function decodeMembership(value: unknown): MembershipResource {
  if (
    !isRecord(value) ||
    !isMembershipStatus(value.status) ||
    !Array.isArray(value.roleAssignments)
  ) {
    throw new ApiContractError("A member response is invalid.");
  }

  return {
    authorizationVersion: requiredNumber(
      value,
      "authorizationVersion",
      "A member response is invalid.",
    ),
    createdAt: requiredString(value, "createdAt", "A member response is invalid."),
    id: requiredString(value, "id", "A member response is invalid."),
    organizationId: requiredString(value, "organizationId", "A member response is invalid."),
    roleAssignments: value.roleAssignments.map(decodeMembershipRoleAssignment),
    status: value.status,
    updatedAt: requiredString(value, "updatedAt", "A member response is invalid."),
    userId: requiredString(value, "userId", "A member response is invalid."),
    version: requiredNumber(value, "version", "A member response is invalid."),
  };
}

function decodeMembershipRoleAssignment(value: unknown): MembershipRoleAssignment {
  if (!isRecord(value) || !isStringArray(value.storeIds)) {
    throw new ApiContractError("A member role assignment is invalid.");
  }
  if (value.expiresAt !== null && typeof value.expiresAt !== "string") {
    throw new ApiContractError("A member role assignment is invalid.");
  }

  return {
    assignmentId: requiredString(value, "assignmentId", "A member role assignment is invalid."),
    expiresAt: value.expiresAt,
    roleId: requiredString(value, "roleId", "A member role assignment is invalid."),
    scopeType: requiredString(value, "scopeType", "A member role assignment is invalid."),
    storeIds: value.storeIds,
  };
}

function decodeRoles(value: unknown): readonly RoleResource[] {
  const items = Array.isArray(value)
    ? value
    : isRecord(value) && Array.isArray(value.member)
      ? value.member
      : undefined;
  if (items === undefined) {
    throw new ApiContractError("The role collection response is invalid.");
  }

  return items.map(decodeRole);
}

function decodeRole(value: unknown): RoleResource {
  if (!isRecord(value) || !isRoleStatus(value.status) || !isRoleType(value.type)) {
    throw new ApiContractError("A role response is invalid.");
  }
  if (value.description !== null && typeof value.description !== "string") {
    throw new ApiContractError("A role response is invalid.");
  }
  if (!isStringArray(value.permissions)) {
    throw new ApiContractError("A role response is invalid.");
  }

  return {
    code: requiredString(value, "code", "A role response is invalid."),
    description: value.description,
    id: requiredString(value, "id", "A role response is invalid."),
    name: requiredString(value, "name", "A role response is invalid."),
    permissions: value.permissions,
    status: value.status,
    type: value.type,
    version: requiredNumber(value, "version", "A role response is invalid."),
  };
}

function decodeCreatedInvitation(value: unknown): CreatedInvitationResource {
  if (!isRecord(value) || typeof value.token !== "string" || !isRecord(value.invitation)) {
    throw new ApiContractError("The created invitation response is invalid.");
  }

  return { invitation: decodeInvitation(value.invitation), token: value.token };
}

function decodeInvitation(value: unknown): InvitationResource {
  const invitation = value;
  if (
    !isRecord(invitation) ||
    !Array.isArray(invitation.roleAssignments) ||
    (invitation.acceptedAt !== null && typeof invitation.acceptedAt !== "string")
  ) {
    throw new ApiContractError("The created invitation response is invalid.");
  }

  return {
    acceptedAt: invitation.acceptedAt,
    email: requiredString(invitation, "email", "The invitation response is invalid."),
    expiresAt: requiredString(invitation, "expiresAt", "The invitation response is invalid."),
    id: requiredString(invitation, "id", "The invitation response is invalid."),
    organizationId: requiredString(
      invitation,
      "organizationId",
      "The invitation response is invalid.",
    ),
    roleAssignments: invitation.roleAssignments.map(decodeInvitationRoleAssignment),
    status: requiredString(invitation, "status", "The invitation response is invalid."),
    version: requiredNumber(invitation, "version", "The invitation response is invalid."),
  };
}

function decodeInvitationRoleAssignment(value: unknown): Readonly<{
  roleCode: string;
  storeIds: readonly string[];
}> {
  if (!isRecord(value) || !isStringArray(value.storeIds)) {
    throw new ApiContractError("The created invitation response is invalid.");
  }

  return {
    roleCode: requiredString(value, "roleCode", "The created invitation response is invalid."),
    storeIds: value.storeIds,
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

function isMembershipStatus(value: unknown): value is MembershipStatus {
  return value === "ACTIVE" || value === "INVITED" || value === "SUSPENDED" || value === "REVOKED";
}

function isRoleStatus(value: unknown): value is RoleStatus {
  return value === "ACTIVE" || value === "ARCHIVED";
}

function isRoleType(value: unknown): value is RoleType {
  return value === "CUSTOM" || value === "SYSTEM";
}

function requiredString(value: Record<string, unknown>, property: string, message: string): string {
  const propertyValue = value[property];
  if (typeof propertyValue !== "string") {
    throw new ApiContractError(message);
  }
  return propertyValue;
}

function requiredProductStatus(
  value: Record<string, unknown>,
  property: string,
  message: string,
): ProductStatus {
  const propertyValue = value[property];
  if (
    propertyValue === "ACTIVE" ||
    propertyValue === "ARCHIVED" ||
    propertyValue === "DRAFT" ||
    propertyValue === "INACTIVE"
  )
    return propertyValue;
  throw new ApiContractError(message);
}

function requiredProductType(
  value: Record<string, unknown>,
  property: string,
  message: string,
): ProductType {
  const propertyValue = value[property];
  if (propertyValue === "PHYSICAL" || propertyValue === "SERVICE") return propertyValue;
  throw new ApiContractError(message);
}

function requiredCategoryStatus(
  value: Record<string, unknown>,
  property: string,
  message: string,
): CategoryStatus {
  const propertyValue = value[property];
  if (propertyValue === "ACTIVE" || propertyValue === "ARCHIVED" || propertyValue === "INACTIVE")
    return propertyValue;
  throw new ApiContractError(message);
}

function requiredPriceListStatus(
  value: Record<string, unknown>,
  property: string,
  message: string,
): PriceListStatus {
  const propertyValue = value[property];
  if (
    propertyValue === "ACTIVE" ||
    propertyValue === "ARCHIVED" ||
    propertyValue === "DRAFT" ||
    propertyValue === "INACTIVE"
  )
    return propertyValue;
  throw new ApiContractError(message);
}

function requiredPriceListScope(
  value: Record<string, unknown>,
  property: string,
  message: string,
): PriceListScope {
  if (value[property] === "ORGANIZATION") return "ORGANIZATION";
  throw new ApiContractError(message);
}

function requiredProductPriceStatus(
  value: Record<string, unknown>,
  property: string,
  message: string,
): ProductPriceStatus {
  const propertyValue = value[property];
  if (propertyValue === "ACTIVE" || propertyValue === "ARCHIVED" || propertyValue === "INACTIVE")
    return propertyValue;
  throw new ApiContractError(message);
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
