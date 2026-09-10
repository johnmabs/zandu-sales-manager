import { render, screen } from "@testing-library/react";
import React from "react";
import { describe, expect, it } from "vitest";

import {
  StoreList,
  storeListErrorPresentation,
} from "../../apps/admin/src/features/stores/components/StoreList";
import { ApiRequestError } from "../../packages/api-client/src/index";

const store = {
  code: "CENTRE",
  currency: "XAF",
  id: "store-1",
  locale: "fr_CG",
  name: "Centre-ville",
  organizationId: "organization-1",
  status: "ACTIVE" as const,
  timeZone: "Africa/Brazzaville",
};

describe("StoreList", () => {
  it("renders loading and the Store contract fields", () => {
    const { rerender } = render(<StoreList canCreate={false} isLoading stores={undefined} />);

    expect(screen.getByRole("status", { name: "Chargement des magasins" })).toBeTruthy();

    rerender(<StoreList canCreate={false} isLoading={false} stores={[store]} />);
    expect(screen.getByRole("table").textContent).toContain("Centre-ville");
    expect(screen.getByRole("table").textContent).toContain("CENTRE");
    expect(screen.getByRole("table").textContent).toContain("ACTIVE");
  });

  it("only proposes creation in the empty state when the client permission is known", () => {
    const { rerender } = render(<StoreList canCreate={false} isLoading={false} stores={[]} />);

    expect(screen.queryByRole("link", { name: "Créer un magasin" })).toBeNull();

    rerender(<StoreList canCreate isLoading={false} stores={[]} />);
    expect(screen.getByRole("link", { name: "Créer un magasin" }).getAttribute("href")).toBe(
      "/app/stores/new",
    );
  });

  it("distinguishes server denial, network failure, and other API failures", () => {
    expect(
      storeListErrorPresentation(new ApiRequestError({ kind: "response", status: 403 }, false))
        .title,
    ).toBe("Accès refusé");
    expect(storeListErrorPresentation(new ApiRequestError({ kind: "network" }, false)).title).toBe(
      "Connexion indisponible",
    );
    expect(
      storeListErrorPresentation(new ApiRequestError({ kind: "response", status: 422 }, false))
        .title,
    ).toBe("Impossible de charger les magasins");
  });
});
