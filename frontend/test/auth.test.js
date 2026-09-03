import assert from "node:assert/strict";
import test from "node:test";

import {
  AuthenticationManager,
  AuthenticationRequiredError,
  AuthorizationVersionInvalidatedError,
} from "../packages/auth/src/index.ts";

const actor = {
  authorizationVersion: 4,
  id: "0198c728-8f2d-7f43-92d8-3f0c75b80186",
  organizationId: "0198c728-8f2d-7f43-92d8-3f0c75b80187",
  userId: "0198c728-8f2d-7f43-92d8-3f0c75b80188",
};

function token({
  authorizationVersion = 4,
  expiresAt = Date.now() + 60_000,
  sessionId = "session-1",
} = {}) {
  const payload = Buffer.from(
    JSON.stringify({ authorizationVersion, exp: Math.floor(expiresAt / 1000), sessionId }),
  ).toString("base64url");

  return `header.${payload}.signature`;
}

function tokens(overrides = {}) {
  return {
    accessToken: token(),
    refreshExpiresAt: "2026-12-01T00:00:00.000Z",
    refreshToken: "opaque-refresh-token",
    ...overrides,
  };
}

function transport(overrides = {}) {
  return {
    login: async () => tokens(),
    logout: async () => undefined,
    refresh: async () => tokens({ refreshToken: "rotated-refresh-token" }),
    resolveActor: async () => actor,
    ...overrides,
  };
}

test("login establishes the actor context with in-memory credentials only", async () => {
  const auth = new AuthenticationManager(transport());

  const state = await auth.login({ email: "owner@zandu.test", password: "password" });

  assert.equal(state.status, "AUTHENTICATED");
  assert.deepEqual(state.actor, actor);
  assert.equal(state.session.authorizationVersion, 4);
  assert.equal(state.session.sessionId, "session-1");
  assert.ok(auth.getAccessToken());
});

test("bootstrap stays unresolved until it has built the actor context", async () => {
  let resolveActor = () => undefined;
  const actorResolution = new Promise((resolve) => {
    resolveActor = resolve;
  });
  const auth = new AuthenticationManager(transport({ resolveActor: async () => actorResolution }));

  const bootstrapping = auth.bootstrap(tokens());

  assert.equal(auth.getState().status, "UNKNOWN");
  resolveActor(actor);
  assert.equal((await bootstrapping).status, "AUTHENTICATED");
});

test("bootstrap refreshes an expired access token before authenticating", async () => {
  let refreshCalls = 0;
  const auth = new AuthenticationManager(
    transport({
      refresh: async () => {
        refreshCalls += 1;
        return tokens({ accessToken: token(), refreshToken: "rotated-refresh-token" });
      },
    }),
  );

  const state = await auth.bootstrap(
    tokens({ accessToken: token({ expiresAt: Date.now() - 60_000 }) }),
  );

  assert.equal(refreshCalls, 1);
  assert.equal(state.status, "AUTHENTICATED");
});

test("concurrent expired requests share one refresh and retry with its access token", async () => {
  let refreshCalls = 0;
  let completeRefresh;
  const refreshResolution = new Promise((resolve) => {
    completeRefresh = resolve;
  });
  const auth = new AuthenticationManager(
    transport({
      refresh: async () => {
        refreshCalls += 1;
        return refreshResolution;
      },
    }),
  );
  await auth.login({ email: "owner@zandu.test", password: "password" });

  const retries = [
    auth.refreshAndRetry(async (accessToken) => accessToken),
    auth.refreshAndRetry(async (accessToken) => accessToken),
  ];
  completeRefresh(tokens({ accessToken: token(), refreshToken: "rotated-refresh-token" }));

  const [first, second] = await Promise.all(retries);
  assert.equal(refreshCalls, 1);
  assert.equal(first, second);
  assert.equal(auth.getState().status, "AUTHENTICATED");
});

test("a failed refresh clears credentials and requires a new login", async () => {
  const auth = new AuthenticationManager(
    transport({ refresh: async () => Promise.reject(new Error("refresh rejected")) }),
  );
  await auth.login({ email: "owner@zandu.test", password: "password" });

  await assert.rejects(auth.refresh(), /refresh rejected/);
  assert.equal(auth.getState().status, "UNAUTHENTICATED");
  assert.equal(auth.getAccessToken(), undefined);
  await assert.rejects(
    auth.refreshAndRetry(async () => "never"),
    AuthenticationRequiredError,
  );
});

test("an invalidated authorization version clears the stale session", async () => {
  const auth = new AuthenticationManager(
    transport({ resolveActor: async () => ({ ...actor, authorizationVersion: 5 }) }),
  );

  await assert.rejects(
    auth.login({ email: "owner@zandu.test", password: "password" }),
    AuthorizationVersionInvalidatedError,
  );
  assert.equal(auth.getState().status, "UNAUTHENTICATED");
  assert.equal(auth.getAccessToken(), undefined);
});

test("logout revokes the current refresh token before clearing the session", async () => {
  const revoked = [];
  const auth = new AuthenticationManager(
    transport({ logout: async (refreshToken) => revoked.push(refreshToken) }),
  );
  await auth.login({ email: "owner@zandu.test", password: "password" });

  await auth.logout();

  assert.deepEqual(revoked, ["opaque-refresh-token"]);
  assert.equal(auth.getState().status, "UNAUTHENTICATED");
});
