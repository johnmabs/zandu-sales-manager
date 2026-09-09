import assert from "node:assert/strict";
import test from "node:test";

import {
  CriticalMutationManager,
  IdempotencyError,
  idempotencyHeaders,
} from "../packages/idempotency/src/index.ts";

test("a retry after an unknown outcome keeps the original Idempotency-Key", () => {
  let generatedKeys = 0;
  const mutations = new CriticalMutationManager(() => {
    generatedKeys += 1;
    return `key-${generatedKeys}`;
  });

  const initial = mutations.begin("complete-sale:cart-17");
  assert.deepEqual(initial, {
    intentId: "complete-sale:cart-17",
    key: "key-1",
    status: "submitting",
  });

  assert.equal(mutations.markUnknownOutcome("complete-sale:cart-17").status, "unknown outcome");
  assert.deepEqual(mutations.begin("complete-sale:cart-17"), initial);
  assert.equal(generatedKeys, 1);
  assert.deepEqual(idempotencyHeaders(initial.key), { "Idempotency-Key": "key-1" });
});

test("critical mutations distinguish success, business failure, and technical failure", () => {
  const mutations = new CriticalMutationManager(() => "key");

  mutations.begin("post-receipt:1");
  assert.equal(mutations.completeSuccess("post-receipt:1").status, "success");
  mutations.retire("post-receipt:1");

  mutations.begin("ship-transfer:1");
  assert.equal(mutations.completeBusinessFailure("ship-transfer:1").status, "business failure");
  mutations.retire("ship-transfer:1");

  mutations.begin("receive-transfer:1");
  assert.equal(
    mutations.completeTechnicalFailure("receive-transfer:1").status,
    "technical failure",
  );
});

test("a known outcome cannot silently become a new attempt for the same intention", () => {
  const mutations = new CriticalMutationManager(() => "key");

  mutations.begin("complete-sale:cart-17");
  mutations.completeSuccess("complete-sale:cart-17");

  assert.throws(() => mutations.begin("complete-sale:cart-17"), IdempotencyError);
  assert.throws(() => idempotencyHeaders(" "), IdempotencyError);
});
