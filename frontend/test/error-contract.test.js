import assert from "node:assert/strict";
import test from "node:test";

import { ErrorMapper } from "../packages/error-contract/src/index.ts";

const mapper = new ErrorMapper();

test("ErrorMapper distinguishes each baseline error category without exposing HTTP status text", () => {
  const cases = [
    [{ kind: "response", status: 400 }, "Informations à corriger", false],
    [{ kind: "response", status: 401 }, "Connexion requise", false],
    [{ kind: "response", status: 403 }, "Action non autorisée", false],
    [{ kind: "response", status: 404 }, "Introuvable", false],
    [{ kind: "response", status: 422 }, "Action impossible", false],
    [{ code: "IDEMPOTENCY_CONFLICT", kind: "response", status: 409 }, "Demande en conflit", false],
    [{ kind: "network" }, "Connexion indisponible", true],
    [{ kind: "response", status: 500 }, "Erreur du service", true],
  ];

  for (const [apiError, title, retryable] of cases) {
    const uiError = mapper.map(apiError);

    assert.equal(uiError.title, title);
    assert.equal(uiError.retryable, retryable);
    assert.doesNotMatch(uiError.message, /HTTP 409/);
  }
});

test("ErrorMapper preserves field and correlation information and maps known business codes", () => {
  const uiError = mapper.map({
    code: "INSUFFICIENT_STOCK",
    correlationId: "0198c728-8f2d-7f43-92d8-3f0c75b80186",
    fieldErrors: { quantity: ["Must be available."] },
    kind: "response",
    status: 422,
  });

  assert.equal(uiError.title, "Stock insuffisant");
  assert.equal(uiError.message, "Stock insuffisant pour finaliser la vente.");
  assert.deepEqual(uiError.fieldErrors, { quantity: ["Must be available."] });
  assert.equal(uiError.correlationId, "0198c728-8f2d-7f43-92d8-3f0c75b80186");
});

test("features can enrich mappings for their own stable business codes", () => {
  const uiError = mapper.map(
    { code: "FEATURE_SPECIFIC_CONFLICT", kind: "response", status: 422 },
    {
      FEATURE_SPECIFIC_CONFLICT: {
        message: "Cette opération nécessite une étape supplémentaire.",
        retryable: false,
        title: "Étape requise",
      },
    },
  );

  assert.equal(uiError.title, "Étape requise");
  assert.equal(uiError.message, "Cette opération nécessite une étape supplémentaire.");
});
