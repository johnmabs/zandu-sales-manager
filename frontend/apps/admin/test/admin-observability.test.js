import assert from "node:assert/strict";
import test from "node:test";

import {
  ADMIN_TELEMETRY_EVENT,
  createAdminObservability,
  createAdminTelemetrySink,
} from "../src/runtime/adminObservability.ts";

test("Admin dispatches bounded Stores telemetry for an observability adapter", () => {
  const dispatched = [];
  const sink = createAdminTelemetrySink((event) => {
    dispatched.push(event);
    return true;
  });
  const telemetry = createAdminObservability("test", "2026.09.13", sink);

  telemetry.recordApiFailure({
    category: "password=must-not-leak",
    correlationId: "correlation-1",
    failureKind: "response",
    feature: "stores",
    method: "POST",
    operation: "request_closure",
    outcomeUnknown: false,
    path: "/stores/0198c728-8f2d-7f43-92d8-3f0c75b80186/closure-request",
    route: "/app/stores/0198c728-8f2d-7f43-92d8-3f0c75b80186?token=secret",
    status: 422,
  });

  assert.equal(dispatched.length, 1);
  assert.equal(dispatched[0].type, ADMIN_TELEMETRY_EVENT);
  assert.deepEqual(dispatched[0].detail, {
    category: "UNKNOWN_RESPONSE",
    clientVersion: "2026.09.13",
    correlationId: "correlation-1",
    environment: "test",
    failureKind: "response",
    feature: "stores",
    kind: "api_failure",
    method: "POST",
    occurredAt: dispatched[0].detail.occurredAt,
    operation: "request_closure",
    outcomeUnknown: false,
    path: "/stores/:id/closure-request",
    route: "/app/stores/:id",
    status: 422,
  });
});
