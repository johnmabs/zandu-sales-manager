import { http, HttpResponse } from "msw";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, describe, expect, it } from "vitest";

import {
  ApiClient,
  ApiRequestError,
  FoundationApi,
  createAuthenticationTransport,
} from "../../packages/api-client/src/index";
import { AuthenticationManager } from "../../packages/auth/src/index";

const server = setupServer(
  http.get("https://api.zandu.test/api/stores", ({ request }) => {
    expect(request.headers.get("authorization")).toBe("Bearer access-token");
    expect(request.headers.get("x-correlation-id")).toBe("correlation-id");

    return HttpResponse.json({ items: [{ id: "store-1", name: "Main store" }] });
  }),
);

beforeAll(() => server.listen({ onUnhandledRequest: "error" }));
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

describe("ApiClient at the mocked API boundary", () => {
  it("sends authenticated requests and decodes the server projection", async () => {
    const client = new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      generateCorrelationId: () => "correlation-id",
      session: {
        getAccessToken: () => "access-token",
        refreshAndRetry: async (retry) => retry("refreshed-access-token"),
      },
    });

    await expect(client.request({ method: "GET", path: "stores" })).resolves.toMatchObject({
      data: { items: [{ id: "store-1", name: "Main store" }] },
      status: 200,
    });
  });

  it("keeps backend validation details at the boundary", async () => {
    server.use(
      http.post("https://api.zandu.test/api/stores", () =>
        HttpResponse.json(
          { code: "VALIDATION_ERROR", fieldErrors: { name: ["Required"] } },
          { status: 422 },
        ),
      ),
    );
    const client = new ApiClient({
      config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
    });

    await expect(
      client.request({ body: {}, method: "POST", path: "stores" }),
    ).rejects.toMatchObject({
      apiError: { code: "VALIDATION_ERROR", fieldErrors: { name: ["Required"] }, status: 422 },
    });
  });

  it("attaches safe Stores feature context to terminal API failures", async () => {
    const failures: unknown[] = [];
    server.use(
      http.get("https://api.zandu.test/api/stores", () =>
        HttpResponse.json(
          { code: "FORBIDDEN", correlationId: "correlation-from-server" },
          { status: 403 },
        ),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
        observability: { recordApiFailure: (failure) => failures.push(failure) },
      }),
    );

    await expect(
      api.listAccessibleStores({
        accessibleStoreIds: [],
        authorizationVersion: 1,
        organizationId: "organization-1",
        permissions: ["STORE_READ"],
        scope: { type: "ORGANIZATION" },
      }),
    ).rejects.toBeInstanceOf(ApiRequestError);

    expect(failures).toEqual([
      {
        category: "FORBIDDEN",
        correlationId: "correlation-from-server",
        failureKind: "response",
        feature: "stores",
        method: "GET",
        operation: "list",
        outcomeUnknown: false,
        path: "stores",
        route: "/admin/stores",
        status: 403,
      },
    ]);
  });

  it("decodes only current-organization members and records bounded Access telemetry", async () => {
    const failures: unknown[] = [];
    server.use(
      http.get("https://api.zandu.test/api/members", () =>
        HttpResponse.json([
          {
            authorizationVersion: 2,
            createdAt: "2026-09-10T08:00:00+00:00",
            id: "membership-1",
            organizationId: "organization-1",
            roleAssignments: [
              {
                assignmentId: "assignment-1",
                expiresAt: null,
                roleId: "STORE_MANAGER",
                scopeType: "SELECTED_STORES",
                storeIds: ["store-1"],
              },
            ],
            status: "ACTIVE",
            updatedAt: "2026-09-10T08:00:00+00:00",
            userId: "user-1",
            version: 1,
          },
          {
            authorizationVersion: 1,
            createdAt: "2026-09-10T08:00:00+00:00",
            id: "membership-other-organization",
            organizationId: "organization-2",
            roleAssignments: [],
            status: "SUSPENDED",
            updatedAt: "2026-09-10T08:00:00+00:00",
            userId: "user-2",
            version: 1,
          },
        ]),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
        observability: { recordApiFailure: (failure) => failures.push(failure) },
      }),
    );

    await expect(
      api.listOrganizationMembers({
        accessibleStoreIds: [],
        authorizationVersion: 2,
        organizationId: "organization-1",
        permissions: ["MEMBER_READ"],
        scope: { type: "ORGANIZATION" },
      }),
    ).resolves.toMatchObject([{ id: "membership-1", userId: "user-1" }]);
    expect(failures).toEqual([]);
  });

  it("keeps an out-of-organization member detail unavailable to the UI", async () => {
    server.use(
      http.get("https://api.zandu.test/api/members/membership-other-organization", () =>
        HttpResponse.json({
          authorizationVersion: 1,
          createdAt: "2026-09-10T08:00:00+00:00",
          id: "membership-other-organization",
          organizationId: "organization-2",
          roleAssignments: [],
          status: "SUSPENDED",
          updatedAt: "2026-09-10T08:00:00+00:00",
          userId: "user-2",
          version: 1,
        }),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );

    await expect(
      api.getOrganizationMember("membership-other-organization", {
        accessibleStoreIds: [],
        authorizationVersion: 1,
        organizationId: "organization-1",
        permissions: ["MEMBER_READ"],
        scope: { type: "ORGANIZATION" },
      }),
    ).resolves.toBeUndefined();
  });

  it("decodes the published read-only role catalog", async () => {
    server.use(
      http.get("https://api.zandu.test/api/roles", () =>
        HttpResponse.json([
          {
            code: "STORE_MANAGER",
            description: "Gère les opérations d’un magasin.",
            id: "role-1",
            name: "Responsable de magasin",
            permissions: ["STORE_READ", "INVENTORY_READ"],
            status: "ACTIVE",
            type: "SYSTEM",
            version: 1,
          },
        ]),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );

    await expect(api.listRoles()).resolves.toMatchObject([
      { code: "STORE_MANAGER", permissions: ["STORE_READ", "INVENTORY_READ"], type: "SYSTEM" },
    ]);
  });

  it("connects AuthenticationManager to scoped Symfony store projections", async () => {
    const organizationId = "0198c728-8f2d-7f43-92d8-3f0c75b80187";
    const accessToken = jwt();
    server.use(
      http.post("https://api.zandu.test/api/auth/login", () =>
        HttpResponse.json({
          refreshExpiresAt: "2026-12-01T00:00:00+00:00",
          token: accessToken,
        }),
      ),
      http.get("https://api.zandu.test/api/session", ({ request }) => {
        expect(request.headers.get("authorization")).toBe(`Bearer ${accessToken}`);
        return HttpResponse.json({
          authorizationVersion: 4,
          effectiveAccess: {
            accessibleStoreIds: ["store-1"],
            authorizationVersion: 4,
            organizationId,
            permissions: ["STORE_READ"],
            scope: { type: "ORGANIZATION" },
          },
          email: "owner@zandu.test",
          id: "actor-1",
          organizationId,
          organizations: [
            {
              defaultCurrency: "XAF",
              defaultLocale: "fr_CG",
              defaultTimeZone: "Africa/Brazzaville",
              id: organizationId,
              name: "Zandu",
              status: "ACTIVE",
            },
          ],
          userId: "user-1",
        });
      }),
      http.get("https://api.zandu.test/api/stores", () =>
        HttpResponse.json([
          {
            address: null,
            code: "CENTRE",
            currency: "XAF",
            id: "store-1",
            locale: "fr_CG",
            name: "Centre-ville",
            organizationId,
            status: "ACTIVE",
            timeZone: "Africa/Brazzaville",
            updatedAt: "2026-09-10T08:00:00+00:00",
            version: 1,
          },
          {
            address: null,
            code: "HORS-SCOPE",
            currency: "XAF",
            id: "store-out-of-scope",
            locale: "fr_CG",
            name: "Inaccessible",
            organizationId,
            status: "ACTIVE",
            timeZone: "Africa/Brazzaville",
            updatedAt: "2026-09-10T08:00:00+00:00",
            version: 1,
          },
          {
            address: null,
            code: "AUTRE-TENANT",
            currency: "XAF",
            id: "store-other-organization",
            locale: "fr_CG",
            name: "Autre organisation",
            organizationId: "organization-2",
            status: "ACTIVE",
            timeZone: "Africa/Brazzaville",
            updatedAt: "2026-09-10T08:00:00+00:00",
            version: 1,
          },
        ]),
      ),
      http.get("https://api.zandu.test/api/stores/:storeId", ({ params }) =>
        HttpResponse.json({
          address: "12 avenue du Port",
          code: params.storeId === "store-1" ? "CENTRE" : "AUTRE-TENANT",
          currency: "XAF",
          id: params.storeId,
          locale: "fr_CG",
          name: "Centre-ville",
          organizationId: params.storeId === "store-1" ? organizationId : "organization-2",
          status: "ACTIVE",
          timeZone: "Africa/Brazzaville",
          updatedAt: "2026-09-10T08:00:00+00:00",
          version: 1,
        }),
      ),
      http.post("https://api.zandu.test/api/stores", async ({ request }) => {
        expect(await request.json()).toEqual({
          address: null,
          code: "NOUVEAU",
          currency: "XAF",
          locale: "fr_CG",
          name: "Nouveau magasin",
          timeZone: "Africa/Brazzaville",
        });
        return HttpResponse.json(
          {
            address: null,
            code: "NOUVEAU",
            currency: "XAF",
            id: "store-new",
            locale: "fr_CG",
            name: "Nouveau magasin",
            organizationId,
            status: "ACTIVE",
            timeZone: "Africa/Brazzaville",
            updatedAt: "2026-09-10T08:00:00+00:00",
            version: 1,
          },
          { status: 201 },
        );
      }),
      http.patch("https://api.zandu.test/api/stores/store-1", async ({ request }) => {
        expect(await request.json()).toEqual({
          address: "15 avenue du Port",
          locale: "fr_CG",
          name: "Centre rénové",
          timeZone: "Africa/Brazzaville",
        });
        return HttpResponse.json({
          address: "15 avenue du Port",
          code: "CENTRE",
          currency: "XAF",
          id: "store-1",
          locale: "fr_CG",
          name: "Centre rénové",
          organizationId,
          status: "ACTIVE",
          timeZone: "Africa/Brazzaville",
          updatedAt: "2026-09-10T09:00:00+00:00",
          version: 2,
        });
      }),
      http.post("https://api.zandu.test/api/stores/store-1/suspend", async ({ request }) => {
        expect(await request.text()).toBe("");
        return HttpResponse.json(
          {
            address: "12 avenue du Port",
            code: "CENTRE",
            currency: "XAF",
            id: "store-1",
            locale: "fr_CG",
            name: "Centre-ville",
            organizationId,
            status: "SUSPENDED",
            timeZone: "Africa/Brazzaville",
            updatedAt: "2026-09-10T10:00:00+00:00",
            version: 2,
          },
          { status: 201 },
        );
      }),
      http.post("https://api.zandu.test/api/stores/store-1/reactivate", async ({ request }) => {
        expect(await request.text()).toBe("");
        return HttpResponse.json(
          {
            address: "12 avenue du Port",
            code: "CENTRE",
            currency: "XAF",
            id: "store-1",
            locale: "fr_CG",
            name: "Centre-ville",
            organizationId,
            status: "ACTIVE",
            timeZone: "Africa/Brazzaville",
            updatedAt: "2026-09-10T10:30:00+00:00",
            version: 3,
          },
          { status: 201 },
        );
      }),
      http.post(
        "https://api.zandu.test/api/stores/store-1/closure-request",
        async ({ request }) => {
          expect(await request.json()).toEqual({ reason: "Fin d’activité" });
          return HttpResponse.json(
            {
              blockers: ["OPEN_CASH_SESSION", "FUTURE_BLOCKER"],
              id: "closure-1",
              reason: "Fin d’activité",
              requestedAt: "2026-09-10T11:00:00+00:00",
              status: "IN_PROGRESS",
              storeId: "store-1",
              version: 1,
            },
            { status: 201 },
          );
        },
      ),
    );
    const config = { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" } as const;
    const auth = new AuthenticationManager(
      createAuthenticationTransport(
        new ApiClient({ config, generateCorrelationId: () => "auth-correlation" }),
      ),
    );

    const authState = await auth.login({ email: "owner@zandu.test", password: "password" });
    expect(authState.actor?.effectiveAccess.permissions).toContain("STORE_READ");
    expect(authState.actor?.organizations[0]?.name).toBe("Zandu");
    if (authState.actor === undefined) {
      throw new Error("The authenticated actor was not resolved.");
    }

    const api = new FoundationApi(
      new ApiClient({
        config,
        generateCorrelationId: () => "resource-correlation",
        session: auth,
      }),
    );
    const stores = await api.listAccessibleStores(authState.actor.effectiveAccess);
    expect(stores.map((store) => store.id)).toEqual(["store-1"]);
    await expect(
      api.getAccessibleStore("store-1", authState.actor.effectiveAccess),
    ).resolves.toMatchObject({
      address: "12 avenue du Port",
      id: "store-1",
    });
    await expect(
      api.getAccessibleStore("store-other-organization", authState.actor.effectiveAccess),
    ).resolves.toBeUndefined();
    await expect(
      api.createStore({
        address: null,
        code: "NOUVEAU",
        currency: "XAF",
        locale: "fr_CG",
        name: "Nouveau magasin",
        timeZone: "Africa/Brazzaville",
      }),
    ).resolves.toMatchObject({ id: "store-new" });
    await expect(
      api.updateStore("store-1", {
        address: "15 avenue du Port",
        locale: "fr_CG",
        name: "Centre rénové",
        timeZone: "Africa/Brazzaville",
      }),
    ).resolves.toMatchObject({ id: "store-1", version: 2 });
    await expect(api.suspendStore("store-1")).resolves.toMatchObject({
      id: "store-1",
      status: "SUSPENDED",
    });
    await expect(api.reactivateStore("store-1")).resolves.toMatchObject({
      id: "store-1",
      status: "ACTIVE",
    });
    await expect(
      api.requestStoreClosure("store-1", { reason: "Fin d’activité" }),
    ).resolves.toMatchObject({
      blockers: ["OPEN_CASH_SESSION", "FUTURE_BLOCKER"],
      id: "closure-1",
      status: "IN_PROGRESS",
    });
  });

  it("keeps server denial and network failure distinct at the Stores boundary", async () => {
    const config = { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" } as const;
    const access = {
      accessibleStoreIds: ["store-1"],
      authorizationVersion: 4,
      organizationId: "organization-1",
      permissions: ["STORE_READ"],
      scope: { type: "ORGANIZATION" as const },
    };
    const api = new FoundationApi(new ApiClient({ config }));

    server.use(
      http.get("https://api.zandu.test/api/stores", () =>
        HttpResponse.json({ code: "FORBIDDEN" }, { status: 403 }),
      ),
    );
    await expect(api.listAccessibleStores(access)).rejects.toSatisfy(
      (error: unknown) =>
        error instanceof ApiRequestError &&
        error.apiError.kind === "response" &&
        error.apiError.status === 403,
    );

    server.use(http.get("https://api.zandu.test/api/stores", () => HttpResponse.error()));
    await expect(api.listAccessibleStores(access)).rejects.toSatisfy(
      (error: unknown) => error instanceof ApiRequestError && error.apiError.kind === "network",
    );
  });

  it("returns the created invitation and one-time token from the API contract", async () => {
    server.use(
      http.post("https://api.zandu.test/api/member-invitations", () =>
        HttpResponse.json({
          invitation: {
            acceptedAt: null,
            email: "member@zandu.test",
            expiresAt: "2026-10-01T08:00:00+00:00",
            id: "invitation-1",
            organizationId: "organization-1",
            roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: ["store-1"] }],
            status: "PENDING",
            version: 1,
          },
          token: "one-time-invitation-secret",
        }),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );

    await expect(
      api.inviteMember({
        email: "member@zandu.test",
        expiresAt: null,
        roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: ["store-1"] }],
      }),
    ).resolves.toEqual({
      invitation: {
        acceptedAt: null,
        email: "member@zandu.test",
        expiresAt: "2026-10-01T08:00:00+00:00",
        id: "invitation-1",
        organizationId: "organization-1",
        roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: ["store-1"] }],
        status: "PENDING",
        version: 1,
      },
      token: "one-time-invitation-secret",
    });
  });

  it("cancels a returned invitation through the dedicated transition", async () => {
    server.use(
      http.post("https://api.zandu.test/api/member-invitations/invitation-1/cancel", () =>
        HttpResponse.json({
          acceptedAt: null,
          email: "member@zandu.test",
          expiresAt: "2026-10-01T08:00:00+00:00",
          id: "invitation-1",
          organizationId: "organization-1",
          roleAssignments: [{ roleCode: "STORE_MANAGER", storeIds: [] }],
          status: "CANCELLED",
          version: 2,
        }),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );

    await expect(api.cancelInvitation("invitation-1")).resolves.toMatchObject({
      id: "invitation-1",
      status: "CANCELLED",
      version: 2,
    });
  });

  it("assigns a role through the member transition and decodes the refreshed member", async () => {
    server.use(
      http.post(
        "https://api.zandu.test/api/members/membership-1/role-assignments",
        async ({ request }) => {
          expect(await request.json()).toEqual({
            expiresAt: null,
            roleId: "role-1",
            scopeType: "ORGANIZATION",
            storeIds: [],
          });
          return HttpResponse.json({
            authorizationVersion: 3,
            createdAt: "2026-09-10T08:00:00+00:00",
            id: "membership-1",
            organizationId: "organization-1",
            roleAssignments: [
              {
                assignmentId: "assignment-1",
                expiresAt: null,
                roleId: "role-1",
                scopeType: "ORGANIZATION",
                storeIds: [],
              },
            ],
            status: "ACTIVE",
            updatedAt: "2026-09-13T08:00:00+00:00",
            userId: "user-1",
            version: 2,
          });
        },
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );
    await expect(
      api.assignMemberRole("membership-1", {
        expiresAt: null,
        roleId: "role-1",
        scopeType: "ORGANIZATION",
        storeIds: [],
      }),
    ).resolves.toMatchObject({ authorizationVersion: 3, id: "membership-1" });
  });

  it("removes a role assignment through its contextual member endpoint", async () => {
    server.use(
      http.delete(
        "https://api.zandu.test/api/members/membership-1/role-assignments/assignment-1",
        () =>
          HttpResponse.json({
            authorizationVersion: 4,
            createdAt: "2026-09-10T08:00:00+00:00",
            id: "membership-1",
            organizationId: "organization-1",
            roleAssignments: [],
            status: "ACTIVE",
            updatedAt: "2026-09-13T08:00:00+00:00",
            userId: "user-1",
            version: 3,
          }),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );
    await expect(api.removeMemberRole("membership-1", "assignment-1")).resolves.toMatchObject({
      authorizationVersion: 4,
      roleAssignments: [],
    });
  });

  it("runs suspend, reactivate, and revoke through dedicated member transitions", async () => {
    const memberProjection = (status: string, authorizationVersion: number) => ({
      authorizationVersion,
      createdAt: "2026-09-10T08:00:00+00:00",
      id: "membership-1",
      organizationId: "organization-1",
      roleAssignments: [],
      status,
      updatedAt: "2026-09-13T08:00:00+00:00",
      userId: "user-1",
      version: authorizationVersion,
    });
    server.use(
      http.post("https://api.zandu.test/api/members/membership-1/suspend", () =>
        HttpResponse.json(memberProjection("SUSPENDED", 2)),
      ),
      http.post("https://api.zandu.test/api/members/membership-1/reactivate", () =>
        HttpResponse.json(memberProjection("ACTIVE", 3)),
      ),
      http.post("https://api.zandu.test/api/members/membership-1/revoke", () =>
        HttpResponse.json(memberProjection("REVOKED", 4)),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );
    await expect(api.suspendMember("membership-1")).resolves.toMatchObject({ status: "SUSPENDED" });
    await expect(api.reactivateMember("membership-1")).resolves.toMatchObject({ status: "ACTIVE" });
    await expect(api.revokeMember("membership-1")).resolves.toMatchObject({ status: "REVOKED" });
  });

  it("preserves permission denial and correlated domain refusal at the Access boundary", async () => {
    server.use(
      http.post("https://api.zandu.test/api/members/membership-1/suspend", () =>
        HttpResponse.json({ code: "FORBIDDEN", correlationId: "denied-1" }, { status: 403 }),
      ),
    );
    const api = new FoundationApi(
      new ApiClient({
        config: { apiBaseUrl: "https://api.zandu.test/api/", appEnvironment: "test" },
      }),
    );
    await expect(api.suspendMember("membership-1")).rejects.toMatchObject({
      apiError: { code: "FORBIDDEN", correlationId: "denied-1", status: 403 },
    });
  });
});

function jwt(): string {
  const payload = Buffer.from(
    JSON.stringify({ authorizationVersion: 4, exp: Math.floor(Date.now() / 1000) + 60 }),
  ).toString("base64url");
  return `header.${payload}.signature`;
}
