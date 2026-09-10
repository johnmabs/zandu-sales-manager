import { Badge } from "@zandu/ui";

import type { StoreResource } from "@zandu/api-client";

export function StoreStatusBadge({ status }: Pick<StoreResource, "status">) {
  const tone =
    status === "ACTIVE"
      ? "success"
      : status === "SUSPENDED" || status === "CLOSURE_PENDING"
        ? "warning"
        : "neutral";

  return <Badge tone={tone}>{status}</Badge>;
}
