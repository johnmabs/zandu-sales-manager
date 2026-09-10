import { describe, expect, it } from "vitest";

import { storeClosureBlockerMessage } from "../../apps/admin/src/features/stores/components/StoreClosureBlockers";
import { storeErrorPresentation } from "../../apps/admin/src/features/stores/components/StoreErrorState";
import {
  createStoreSchema,
  toCreateStoreInput,
} from "../../apps/admin/src/features/stores/schemas/createStoreSchema";
import {
  toUpdateStoreInput,
  updateStoreSchema,
} from "../../apps/admin/src/features/stores/schemas/updateStoreSchema";
import { ApiRequestError } from "../../packages/api-client/src/index";

describe("Store form payloads", () => {
  it("accepts the published creation fields and represents an omitted address as null", () => {
    const values = createStoreSchema.parse({
      address: "",
      code: "CENTRE",
      currency: "XAF",
      locale: "fr_CG",
      name: "Centre-ville",
      timeZone: "Africa/Brazzaville",
    });

    expect(toCreateStoreInput(values)).toEqual({
      address: null,
      code: "CENTRE",
      currency: "XAF",
      locale: "fr_CG",
      name: "Centre-ville",
      timeZone: "Africa/Brazzaville",
    });
  });

  it("keeps client validation structural and leaves business validation to the API", () => {
    expect(createStoreSchema.safeParse({ code: "CENTRE" }).success).toBe(false);
  });

  it("maps an update exclusively to contractually mutable fields", () => {
    const values = updateStoreSchema.parse({
      address: "",
      code: "CENTRE",
      currency: "XAF",
      locale: "fr_CG",
      name: "Centre-ville rénové",
      timeZone: "Africa/Brazzaville",
    });

    expect(toUpdateStoreInput(values)).toEqual({
      address: null,
      locale: "fr_CG",
      name: "Centre-ville rénové",
      timeZone: "Africa/Brazzaville",
    });
  });
});

describe("Store presentation mappings", () => {
  it("translates known StoreClosure blockers and preserves an unknown diagnostic code", () => {
    expect(storeClosureBlockerMessage("OPEN_CASH_SESSION")).toBe(
      "Une session de caisse est encore ouverte.",
    );
    expect(storeClosureBlockerMessage("FUTURE_BLOCKER")).toContain("FUTURE_BLOCKER");
  });

  it("keeps server denial distinct from an inaccessible Store resource", () => {
    expect(
      storeErrorPresentation(
        new ApiRequestError({ kind: "response", status: 403 }, false),
        "details",
      ).title,
    ).toBe("Accès refusé");
    expect(
      storeErrorPresentation(
        new ApiRequestError({ kind: "response", status: 404 }, false),
        "details",
      ).title,
    ).toBe("Magasin introuvable");
  });
});
