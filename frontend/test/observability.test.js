import assert from "node:assert/strict";
import test from "node:test";

import { ApiClient } from "../packages/api-client/src/index.ts";
import { FrontendObservability } from "../packages/observability/src/index.ts";

function observability(events, now = () => 0) {
  return new FrontendObservability({
    metadata: { clientVersion: "2026.09.09", environment: "test" },
    now,
    sink: { emit: (event) => events.push(event) },
  });
}

test("frontend observability emits bounded route and technical events without user data", () => {
  const events = [];
  const clock = [1_000, 1_125, 2_000, 2_000];
  const telemetry = observability(events, () => clock.shift());
  const complete = telemetry.startRouteLoad(
    "/admin/stores/0198c728-8f2d-7f43-92d8-3f0c75b80186?customer=secret",
  );

  complete();
  complete();
  telemetry.recordTechnicalError(new TypeError("password=never-exported"), "window");

  assert.deepEqual(events, [
    {
      clientVersion: "2026.09.09",
      durationMs: 125,
      environment: "test",
      kind: "route_load",
      occurredAt: "1970-01-01T00:00:02.000Z",
      route: "/admin/stores/:id",
    },
    {
      clientVersion: "2026.09.09",
      environment: "test",
      errorType: "TypeError",
      kind: "technical_error",
      occurredAt: "1970-01-01T00:00:02.000Z",
      source: "window",
    },
  ]);
});

test("API failures keep correlation and operational metadata but exclude credentials and payloads", async () => {
  const events = [];
  const telemetry = observability(events);
  const client = new ApiClient({
    config: { apiBaseUrl: "https://api.zandu.test/", appEnvironment: "test" },
    fetchImplementation: async () =>
      new Response(
        JSON.stringify({ code: "VALIDATION_ERROR", fieldErrors: { name: ["Required"] } }),
        {
          headers: { "X-Correlation-ID": "correlation-from-server" },
          status: 422,
        },
      ),
    generateCorrelationId: () => "correlation-from-client",
    observability: telemetry,
    session: {
      getAccessToken: () => "access-token-must-not-leak",
      refreshAndRetry: async (retry) => retry("refreshed-token-must-not-leak"),
    },
  });

  await assert.rejects(() =>
    client.request({
      body: { password: "never-exported" },
      method: "POST",
      path: "stores",
      telemetry: { feature: "stores", operation: "create", route: "/admin/stores/new?email=secret" },
    }),
  );

  assert.deepEqual(events, [
    {
      clientVersion: "2026.09.09",
      category: "VALIDATION_ERROR",
      correlationId: "correlation-from-server",
      environment: "test",
      failureKind: "response",
      feature: "stores",
      kind: "api_failure",
      method: "POST",
      operation: "create",
      occurredAt: "1970-01-01T00:00:00.000Z",
      outcomeUnknown: false,
      path: "/stores",
      route: "/admin/stores/new",
      status: 422,
    },
  ]);
});
