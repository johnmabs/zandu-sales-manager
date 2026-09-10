import { http, HttpResponse } from "msw";
import { setupServer } from "msw/node";
import { afterAll, afterEach, beforeAll, describe, expect, it } from "vitest";

import {
  ApiClient,
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
  });
});

function jwt(): string {
  const payload = Buffer.from(
    JSON.stringify({ authorizationVersion: 4, exp: Math.floor(Date.now() / 1000) + 60 }),
  ).toString("base64url");
  return `header.${payload}.signature`;
}
