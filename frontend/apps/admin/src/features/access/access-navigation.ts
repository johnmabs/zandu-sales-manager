import { canViewNavigation } from "@zandu/authorization";

import type { EffectiveAccess, NavigationItem } from "@zandu/authorization";

export type AccessNavigationItem = NavigationItem &
  Readonly<{
    href: string;
    label: string;
  }>;

export const accessNavigation: readonly AccessNavigationItem[] = [
  {
    capabilities: [{ permission: "MEMBER_READ" }],
    href: "/admin/access/members",
    label: "Membres",
  },
  {
    capabilities: [{ permission: "MEMBER_INVITE" }],
    href: "/admin/access/invitations",
    label: "Invitations",
  },
  {
    capabilities: [{ permission: "ROLE_READ" }],
    href: "/admin/access/roles",
    label: "Rôles",
  },
];

/** Navigation is a UX projection only; every route and operation stays server-authorized. */
export function visibleAccessNavigation(
  access: EffectiveAccess | undefined,
): readonly AccessNavigationItem[] {
  return accessNavigation.filter((item) => canViewNavigation(access, item));
}
