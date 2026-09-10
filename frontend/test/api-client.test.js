import assert from "node:assert/strict";
import test from "node:test";

import {
  ApiClient,
  ApiRequestError,
  FoundationApi,
  createAuthenticationTransport,
} from "../packages/api-client/src/index.ts";
import { publicRuntimeConfig } from "../packages/config/src/index.ts";

const config = publicRuntimeConfig({
  API_BASE_URL: "https://api.zandu.test/",
  APP_ENV: "test",
});

test("default fetch preserves the browser global receiver", async (t) => {
  t.mock.method(globalThis, "fetch", function (url, init) {
    if (this !== globalThis) {
      throw new TypeError("Illegal invocation");
    }
    assert.equal(url, "https://api.zandu.test/auth/login");
    assert.equal(init.method, "POST");
    return Promise.resolve(new Response(JSON.stringify({ ok: true })));
  });
  const client = new ApiClient({ config, generateCorrelationId: () => "correlation" });

  const response = await client.request({
    body: { email: "owner@example.com", password: "password" },
    method: "POST",
    path: "auth/login",
    requiresAuthentication: false,
  });

  assert.deepEqual(response.data, { ok: true });
});

test("API client centralizes public configuration and critical request headers", async () => {
  let request;
  const client = new ApiClient({
    config,
    fetchImplementation: async (url, init) => {
      request = { init, url };
      return new Response(JSON.stringify({ id: "sale-1" }), {
        headers: { "X-Correlation-ID": "correlation-from-server" },
        status: 201,
      });
    },
    generateCorrelationId: () => "correlation-from-client",
    session: {
      getAccessToken: () => "access-token",
      refreshAndRetry: async (retry) => retry("refreshed-token"),
    },
  });

  const response = await client.request({
    body: { total: "1200.00" },
    idempotencyKey: "sale-command-1",
    method: "POST",
    path: "/sales/complete",
  });

  assert.deepEqual(response, {
    correlationId: "correlation-from-server",
    data: { id: "sale-1" },
    status: 201,
  });
  assert.equal(request.url, "https://api.zandu.test/sales/complete");
  assert.equal(request.init.credentials, "include");
  assert.equal(request.init.headers.Authorization, "Bearer access-token");
  assert.equal(request.init.headers["Idempotency-Key"], "sale-command-1");
  assert.equal(request.init.headers["X-Correlation-ID"], "correlation-from-client");
});

test("API client refreshes once after a 401 and decodes the resulting response", async () => {
  const authorizationHeaders = [];
  const client = new ApiClient({
    config,
    fetchImplementation: async (_url, init) => {
      authorizationHeaders.push(init.headers.Authorization);
      return authorizationHeaders.length === 1
        ? new Response(JSON.stringify({ code: "TOKEN_EXPIRED" }), { status: 401 })
        : new Response(JSON.stringify({ ok: true }), { status: 200 });
    },
    generateCorrelationId: () => "correlation",
    session: {
      getAccessToken: () => "expired-token",
      refreshAndRetry: async (retry) => retry("refreshed-token"),
    },
  });

  assert.deepEqual(await client.request({ method: "GET", path: "/me" }), {
    data: { ok: true },
    status: 200,
  });
  assert.deepEqual(authorizationHeaders, ["Bearer expired-token", "Bearer refreshed-token"]);
});

test("Symfony auth transport maps token to accessToken and accepts a 204 logout", async () => {
  const requests = [];
  const client = new ApiClient({
    config,
    fetchImplementation: async (url, init) => {
      requests.push({ body: init.body, url });
      if (url.endsWith("auth/logout")) {
        return new Response(null, { status: 204 });
      }
      return new Response(
        JSON.stringify({
          refreshExpiresAt: "2026-12-01T00:00:00+00:00",
          token: "symfony-jwt",
        }),
        { status: 200 },
      );
    },
    generateCorrelationId: () => "correlation",
  });
  const auth = createAuthenticationTransport(client);

  assert.deepEqual(await auth.login({ email: "owner@example.com", password: "password" }), {
    accessToken: "symfony-jwt",
    refreshExpiresAt: "2026-12-01T00:00:00+00:00",
  });
  assert.deepEqual(await auth.refresh(), {
    accessToken: "symfony-jwt",
    refreshExpiresAt: "2026-12-01T00:00:00+00:00",
  });
  await assert.doesNotReject(auth.logout());
  assert.equal(requests.length, 3);
  assert.deepEqual(
    requests.map(({ body }) => body),
    [JSON.stringify({ email: "owner@example.com", password: "password" }), undefined, undefined],
  );
});

test("FoundationApi sends a closure request to the dedicated workflow endpoint", async () => {
  let request;
  const client = new ApiClient({
    config,
    fetchImplementation: async (url, init) => {
      request = { init, url };
      return new Response(
        JSON.stringify({
          blockers: ["OPEN_CASH_SESSION"],
          id: "closure-1",
          reason: "Fin d’activité",
          requestedAt: "2026-09-10T08:00:00+00:00",
          status: "IN_PROGRESS",
          storeId: "store-1",
          version: 1,
        }),
        { status: 201 },
      );
    },
    generateCorrelationId: () => "correlation",
  });

  const closure = await new FoundationApi(client).requestStoreClosure("store-1", {
    reason: "Fin d’activité",
  });

  assert.equal(request.url, "https://api.zandu.test/stores/store-1/closure-request");
  assert.equal(request.init.method, "POST");
  assert.equal(request.init.body, JSON.stringify({ reason: "Fin d’activité" }));
  assert.deepEqual(closure, {
    blockers: ["OPEN_CASH_SESSION"],
    id: "closure-1",
    reason: "Fin d’activité",
    requestedAt: "2026-09-10T08:00:00+00:00",
    status: "IN_PROGRESS",
    storeId: "store-1",
    version: 1,
  });
});

test("idempotent command timeout reports an unknown outcome and preserves server error context", async () => {
  const timeoutClient = new ApiClient({
    config,
    defaultTimeoutMs: 1,
    fetchImplementation: async (_url, init) =>
      new Promise((_resolve, reject) => {
        init.signal?.addEventListener("abort", () => reject(new Error("aborted")));
      }),
    generateCorrelationId: () => "correlation",
  });

  await assert.rejects(
    () =>
      timeoutClient.request({
        idempotencyKey: "command-1",
        method: "POST",
        path: "/sales/complete",
      }),
    (error) => error instanceof ApiRequestError && error.outcomeUnknown,
  );

  const failingClient = new ApiClient({
    config,
    fetchImplementation: async () =>
      new Response(
        JSON.stringify({ code: "INSUFFICIENT_STOCK", correlationId: "server-reference" }),
        {
          status: 422,
        },
      ),
    generateCorrelationId: () => "correlation",
  });

  await assert.rejects(
    () => failingClient.request({ method: "POST", path: "/sales/complete" }),
    (error) =>
      error instanceof ApiRequestError &&
      error.apiError.kind === "response" &&
      error.apiError.code === "INSUFFICIENT_STOCK" &&
      error.apiError.correlationId === "server-reference",
  );
});

test("public configuration rejects absent, secret-bearing, or invalid API URLs", () => {
  assert.throws(() => publicRuntimeConfig({ API_BASE_URL: undefined, APP_ENV: "test" }));
  assert.throws(() =>
    publicRuntimeConfig({ API_BASE_URL: "https://secret@example.test", APP_ENV: "test" }),
  );
  assert.throws(() =>
    publicRuntimeConfig({ API_BASE_URL: "https://api.zandu.test", APP_ENV: "stage" }),
  );
});
