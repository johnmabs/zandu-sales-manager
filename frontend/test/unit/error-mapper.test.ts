import { describe, expect, it } from "vitest";

import { ErrorMapper } from "../../packages/error-contract/src/index";

describe("ErrorMapper", () => {
  it("maps a known business conflict to an actionable UX error", () => {
    const error = new ErrorMapper().map({
      code: "INSUFFICIENT_STOCK",
      correlationId: "0198c728-8f2d-7f43-92d8-3f0c75b80186",
      kind: "response",
      status: 409,
    });

    expect(error).toMatchObject({
      correlationId: "0198c728-8f2d-7f43-92d8-3f0c75b80186",
      message: "Stock insuffisant pour finaliser la vente.",
      retryable: false,
      title: "Stock insuffisant",
    });
  });
});
