import { render, screen } from "@testing-library/react";
import React from "react";
import { describe, expect, it } from "vitest";

import { StoreStatusBadge } from "../../apps/admin/src/features/stores/components/StoreStatusBadge";

describe("StoreStatusBadge", () => {
  it.each([
    ["ACTIVE", "success"],
    ["SUSPENDED", "warning"],
    ["CLOSURE_PENDING", "warning"],
    ["CLOSED", "neutral"],
  ] as const)("renders %s with the %s status tone", (status, tone) => {
    render(<StoreStatusBadge status={status} />);

    expect(screen.getByText(status).className).toContain(`zandu-badge--${tone}`);
  });
});
