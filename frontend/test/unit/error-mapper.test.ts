import { describe, expect, it } from "vitest";

import { accessErrorPresentation } from "../../apps/admin/src/features/access/accessErrors";
import { ApiRequestError } from "../../packages/api-client/src/index";
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

describe("Access errors", () => {
  it("maps authorization failures and preserves correlation", () => {
    expect(
      accessErrorPresentation(
        new ApiRequestError(
          { code: "FORBIDDEN", correlationId: "correlation-1", kind: "response", status: 403 },
          false,
        ),
      ),
    ).toMatchObject({
      correlationId: "correlation-1",
      title: "Action non autorisée",
    });
  });
});
