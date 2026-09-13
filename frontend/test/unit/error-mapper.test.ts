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

  it("explains the last-owner invariant only when the server identifies it", () => {
    expect(
      accessErrorPresentation(
        new ApiRequestError(
          {
            code: "DOMAIN_RULE_VIOLATION",
            correlationId: "correlation-owner",
            kind: "response",
            message: "The last active organization owner cannot be suspended or revoked.",
            status: 409,
          },
          false,
        ),
      ),
    ).toMatchObject({
      correlationId: "correlation-owner",
      message: "L’organisation doit conserver au moins un propriétaire actif.",
      title: "Dernier propriétaire actif",
    });
  });
});
