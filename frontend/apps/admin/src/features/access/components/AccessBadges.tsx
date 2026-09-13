import { Badge } from "@zandu/ui";

import { expirationLabel } from "../expirationPresentation";

export function MembershipStatusBadge({ status }: Readonly<{ status: string }>) {
  const known =
    status === "ACTIVE" || status === "INVITED" || status === "SUSPENDED" || status === "REVOKED";
  const tone =
    status === "ACTIVE"
      ? "success"
      : status === "SUSPENDED"
        ? "warning"
        : status === "REVOKED"
          ? "danger"
          : "neutral";
  return <Badge tone={tone}>{known ? status : `Statut inconnu : ${status}`}</Badge>;
}

export function RoleStatusBadge({ status }: Readonly<{ status: string }>) {
  return (
    <Badge tone={status === "ACTIVE" ? "success" : "neutral"}>
      {status === "ACTIVE" || status === "ARCHIVED" ? status : `Statut inconnu : ${status}`}
    </Badge>
  );
}

export function ScopeBadge({ scopeType }: Readonly<{ scopeType: string }>) {
  return (
    <Badge>
      {scopeType === "ORGANIZATION"
        ? "Organisation entière"
        : scopeType === "SELECTED_STORES"
          ? "Magasins sélectionnés"
          : `Portée inconnue : ${scopeType}`}
    </Badge>
  );
}

export function ExpirationBadge({ expiresAt }: Readonly<{ expiresAt: string | null }>) {
  return (
    <Badge tone={expiresAt === null ? "neutral" : "warning"}>{expirationLabel(expiresAt)}</Badge>
  );
}
