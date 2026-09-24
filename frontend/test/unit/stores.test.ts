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
import { availableStoreActions } from "../../apps/admin/src/features/stores/storeAuthorization";
import { ApiRequestError } from "../../packages/api-client/src/index";

import type { EffectiveAccess } from "../../packages/authorization/src/index";

const storeAccess: EffectiveAccess = {
  accessibleStoreIds: ["store-1"],
  authorizationVersion: 3,
  organizationId: "organization-1",
  permissions: ["STORE_READ", "STORE_UPDATE", "STORE_SUSPEND", "STORE_CLOSE"],
  scope: { type: "ORGANIZATION" },
};

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

    expect(toUpdateStoreInput(values, 3)).toEqual({
      address: null,
      expectedVersion: 3,
      locale: "fr_CG",
      name: "Centre-ville rénové",
      timeZone: "Africa/Brazzaville",
    });
  });
});

describe("Store presentation mappings", () => {
  it("projects only lifecycle actions valid for each published Store status", () => {
    const actions = (status: "ACTIVE" | "SUSPENDED" | "CLOSURE_PENDING" | "CLOSED") =>
      availableStoreActions({
        access: storeAccess,
        organizationId: "organization-1",
        status,
        storeId: "store-1",
      });

    expect(actions("ACTIVE")).toEqual({
      cancelClosure: false,
      edit: true,
      reactivate: false,
      requestClosure: true,
      suspend: true,
    });
    expect(actions("SUSPENDED")).toEqual({
      cancelClosure: false,
      edit: false,
      reactivate: true,
      requestClosure: true,
      suspend: false,
    });
    expect(actions("CLOSURE_PENDING")).toEqual({
      cancelClosure: true,
      edit: false,
      reactivate: false,
      requestClosure: false,
      suspend: false,
    });
    expect(actions("CLOSED")).toEqual({
      cancelClosure: false,
      edit: false,
      reactivate: false,
      requestClosure: false,
      suspend: false,
    });
  });

  it("fails closed when permissions, organization, or Store scope do not match", () => {
    const actions = (access: EffectiveAccess, organizationId = "organization-1") =>
      availableStoreActions({ access, organizationId, status: "ACTIVE", storeId: "store-1" });
    const unavailable = {
      cancelClosure: false,
      edit: false,
      reactivate: false,
      requestClosure: false,
      suspend: false,
    };

    expect(actions({ ...storeAccess, permissions: ["STORE_READ"] })).toEqual(unavailable);
    expect(actions(storeAccess, "organization-2")).toEqual(unavailable);
    expect(
      actions({
        ...storeAccess,
        accessibleStoreIds: [],
        scope: { storeIds: [], type: "SELECTED_STORES" },
      }),
    ).toEqual(unavailable);
  });

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
